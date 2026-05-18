<?php

$codigo = $argv[1] ?? null;
$configs = [
    'ar1516' => ['league_id' => 636, 'season_id' => 7915, 'db' => 'whatif_ar2015', 'season_label' => '2015'],
];

if (!$codigo || !isset($configs[$codigo])) {
    $keys = implode('|', array_keys($configs));
    die("Uso: php import_sportmonks.php [$keys]\n");
}

$config = $configs[$codigo];

$apiKeyFile = __DIR__ . '/../apisportmonks.txt';
if (!file_exists($apiKeyFile)) {
    die("API key no encontrada en $apiKeyFile\n");
}
$apiKey = trim(file_get_contents($apiKeyFile));
$baseUrl = 'https://api.sportmonks.com/v3/football';

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'root';

try {
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['db']}`");

    $schemaFile = __DIR__ . '/schema.sql';
    $schema = file_get_contents($schemaFile);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "Base de datos {$config['db']} preparada.\n";

} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage() . "\n");
}

function apiRequest($url) {
    global $baseUrl, $apiKey;
    $sep = strpos($url, '?') !== false ? '&' : '?';
    $fullUrl = $baseUrl . $url . $sep . 'api_token=' . $apiKey;

    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("HTTP $httpCode: $response");
    }

    $data = json_decode($response, true);
    if (isset($data['rate_limit'])) {
        $rl = $data['rate_limit'];
        $rem = $rl['remaining'] ?? '?';
        echo "  [API: $rem remaining]\n";
    }
    return $data;
}

echo "=== Importando {$config['db']} (liga {$config['league_id']}, temporada {$config['season_label']}) ===\n\n";

// 1. Obtener todos los fixtures
echo "Obteniendo fixtures...\n";
$allFixtureIds = [];
$page = 1;
do {
    $response = apiRequest("/fixtures?filters=fixtureSeasons:{$config['season_id']}&per_page=25&page=$page");
    $fixtures = $response['data'] ?? [];
    foreach ($fixtures as $f) {
        $allFixtureIds[] = $f['id'];
    }
    $hasMore = $response['pagination']['has_more'] ?? false;
    echo "  Página $page: " . count($fixtures) . " fixtures (total: " . count($allFixtureIds) . ")\n";
    $page++;
    usleep(200000);
} while ($hasMore);

echo "Total fixtures: " . count($allFixtureIds) . "\n\n";

// 2. Preparar statements
$stmtEquipo = $pdo->prepare('INSERT IGNORE INTO equipos (id, nombre, nombre_corto) VALUES (?, ?, ?)');
$stmtPartido = $pdo->prepare('INSERT IGNORE INTO partidos (id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, fecha, jornada) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmtJugador = $pdo->prepare('INSERT IGNORE INTO jugadores (id, nombre, equipo_id, dorsal) VALUES (?, ?, ?, ?)');
$stmtAlineacion = $pdo->prepare('INSERT IGNORE INTO alineaciones (partido_id, jugador_id, equipo_id) VALUES (?, ?, ?)');
$stmtEvento = $pdo->prepare('INSERT IGNORE INTO eventos_partido (partido_id, jugador_id, asistente_id, equipo_id, tipo_evento, minuto) VALUES (?, ?, ?, ?, ?, ?)');

$equipos = [];
$jugadoresInsertados = [];
$procesados = 0;
$total = count($allFixtureIds);

echo "Procesando fixtures (events + lineups + scores)...\n";

// 3. Procesar cada fixture
foreach ($allFixtureIds as $fixtureId) {
    try {
        $response = apiRequest("/fixtures/$fixtureId?include=events;lineups;scores;participants");
        $detail = $response['data'] ?? null;
        if (!$detail) {
            echo "  WARN: fixture $fixtureId sin datos\n";
            continue;
        }
    } catch (Exception $e) {
        echo "  ERROR fixture $fixtureId: " . $e->getMessage() . "\n";
        continue;
    }

    // --- Equipos ---
    $localId = null;
    $visitanteId = null;
    foreach ($detail['participants'] ?? [] as $p) {
        $pid = $p['id'];
        if (!isset($equipos[$pid])) {
            $equipos[$pid] = true;
            $stmtEquipo->execute([$pid, $p['name'], mb_substr($p['name'], 0, 20)]);
        }
        $loc = $p['meta']['location'] ?? '';
        if ($loc === 'home') $localId = $pid;
        if ($loc === 'away') $visitanteId = $pid;
    }

    if (!$localId || !$visitanteId) {
        echo "  WARN: fixture $fixtureId sin home/away\n";
        continue;
    }

    // --- Marcador final (type_id=1525 = CURRENT) ---
    $golesLocal = 0;
    $golesVisitante = 0;
    foreach ($detail['scores'] ?? [] as $score) {
        if ($score['type_id'] == 1525) {
            if ($score['participant_id'] == $localId) {
                $golesLocal = $score['score']['goals'];
            } elseif ($score['participant_id'] == $visitanteId) {
                $golesVisitante = $score['score']['goals'];
            }
        }
    }

    // --- Partido ---
    $fecha = substr($detail['starting_at'] ?? '', 0, 10);
    $jornada = $detail['round_id'] ?? 0;
    $stmtPartido->execute([$fixtureId, $localId, $visitanteId, $golesLocal, $golesVisitante, $fecha, $jornada]);

    // --- Alineaciones ---
    foreach ($detail['lineups'] ?? [] as $lu) {
        $jugadorId = $lu['player_id'];
        $equipoId = $lu['team_id'] ?? $lu['participant_id'] ?? null;
        $jersey = $lu['jersey_number'] ?? 0;

        if (!isset($jugadoresInsertados[$jugadorId])) {
            $stmtJugador->execute([$jugadorId, $lu['player_name'] ?? '', $equipoId, $jersey]);
            $jugadoresInsertados[$jugadorId] = true;
        }

        $stmtAlineacion->execute([$fixtureId, $jugadorId, $equipoId]);
    }

    // --- Eventos (goles) ---
    foreach ($detail['events'] ?? [] as $evt) {
        if (!in_array($evt['type_id'], [14, 15, 16])) continue;

        $tipo = 'goal';
        if ($evt['type_id'] == 15) $tipo = 'own_goal';
        if ($evt['type_id'] == 16) $tipo = 'penalty';

        $asistenteId = $evt['related_player_id'] ?? null;
        $teamId = $evt['participant_id'] ?? $evt['team_id'] ?? 0;

        $stmtEvento->execute([$fixtureId, $evt['player_id'], $asistenteId, $teamId, $tipo, $evt['minute']]);
    }

    $procesados++;
    if ($procesados % 10 == 0) {
        echo "  Procesados: $procesados/$total\n";
    }

    usleep(150000);
}

echo "\nEquipos insertados: " . count($equipos) . "\n";
echo "Jugadores insertados: " . count($jugadoresInsertados) . "\n";
echo "Partidos procesados: $procesados\n\n";

// 4. Calcular clasificación
echo "Calculando clasificación...\n";
$contadores = [];
foreach (array_keys($equipos) as $eqId) {
    $contadores[$eqId] = [
        'jugados' => 0, 'ganados' => 0, 'empatados' => 0, 'perdidos' => 0,
        'gf' => 0, 'gc' => 0, 'puntos' => 0,
    ];
}

$rows = $pdo->query('SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante FROM partidos')->fetchAll(PDO::FETCH_OBJ);
foreach ($rows as $r) {
    $local = $r->equipo_local_id;
    $visitante = $r->equipo_visitante_id;

    if (!isset($contadores[$local]) || !isset($contadores[$visitante])) continue;

    $contadores[$local]['jugados']++;
    $contadores[$visitante]['jugados']++;
    $contadores[$local]['gf'] += $r->goles_local;
    $contadores[$local]['gc'] += $r->goles_visitante;
    $contadores[$visitante]['gf'] += $r->goles_visitante;
    $contadores[$visitante]['gc'] += $r->goles_local;

    if ($r->goles_local > $r->goles_visitante) {
        $contadores[$local]['ganados']++;
        $contadores[$visitante]['perdidos']++;
        $contadores[$local]['puntos'] += 3;
    } elseif ($r->goles_local < $r->goles_visitante) {
        $contadores[$visitante]['ganados']++;
        $contadores[$local]['perdidos']++;
        $contadores[$visitante]['puntos'] += 3;
    } else {
        $contadores[$local]['empatados']++;
        $contadores[$visitante]['empatados']++;
        $contadores[$local]['puntos']++;
        $contadores[$visitante]['puntos']++;
    }
}

uasort($contadores, function ($a, $b) {
    if ($b['puntos'] !== $a['puntos']) return $b['puntos'] - $a['puntos'];
    $gdA = $a['gf'] - $a['gc'];
    $gdB = $b['gf'] - $b['gc'];
    return $gdB - $gdA;
});

$stmtClas = $pdo->prepare('INSERT INTO clasificacion (equipo_id, posicion, jugados, ganados, empatados, perdidos, goles_favor, goles_contra, diferencia_goles, puntos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$pos = 1;
foreach ($contadores as $eqId => $c) {
    $stmtClas->execute([
        $eqId, $pos++, $c['jugados'], $c['ganados'], $c['empatados'],
        $c['perdidos'], $c['gf'], $c['gc'], $c['gf'] - $c['gc'], $c['puntos'],
    ]);
}

echo "Clasificación guardada.\n";
echo "\n=== Importación completada: {$config['db']} ===\n";
