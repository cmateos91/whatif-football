<?php
// Batch importer: multiple seasons for a league via Sportmonks API
// Usage: php import_sportmonks_batch.php es

$codigo = $argv[1] ?? null;

$batchConfigs = [
    'es' => [
        'league_id' => 564,
        'liga_nombre' => 'La Liga',
        'liga_id' => 1, // existing in whatif_master
        'seasons' => [
            ['season_id' => 853,   'db' => 'whatif_es1617', 'nombre' => '16/17'],
            ['season_id' => 8442,  'db' => 'whatif_es1718', 'nombre' => '17/18'],
            ['season_id' => 13133, 'db' => 'whatif_es1819', 'nombre' => '18/19'],
            ['season_id' => 16326, 'db' => 'whatif_es1920', 'nombre' => '19/20'],
            ['season_id' => 17480, 'db' => 'whatif_es2021', 'nombre' => '20/21'],
            ['season_id' => 18462, 'db' => 'whatif_es2122', 'nombre' => '21/22'],
            ['season_id' => 19799, 'db' => 'whatif_es2223', 'nombre' => '22/23'],
            ['season_id' => 21694, 'db' => 'whatif_es2324', 'nombre' => '23/24'],
            ['season_id' => 23621, 'db' => 'whatif_es2425', 'nombre' => '24/25'],
        ],
    ],
];

if (!$codigo || !isset($batchConfigs[$codigo])) {
    $keys = implode('|', array_keys($batchConfigs));
    die("Uso: php import_sportmonks_batch.php [$keys]\n");
}

$config = $batchConfigs[$codigo];

$apiKeyFile = __DIR__ . '/../apisportmonks.txt';
$apiKey = trim(file_get_contents($apiKeyFile));
$baseUrl = 'https://api.sportmonks.com/v3/football';

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'root';

function apiRequest($url) {
    global $baseUrl, $apiKey;
    $sep = strpos($url, '?') !== false ? '&' : '?';
    $fullUrl = $baseUrl . $url . $sep . 'api_token=' . $apiKey;
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("HTTP $code");
    $data = json_decode($resp, true);
    if (isset($data['rate_limit'])) {
        printf("  [API: %d remaining]\n", $data['rate_limit']['remaining'] ?? 0);
    }
    return $data;
}

$pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schemaFile = __DIR__ . '/schema.sql';
$schema = file_get_contents($schemaFile);
$schemaStatements = array_filter(array_map('trim', explode(';', $schema)));

// Track next temporada ID
$nextTemporadaId = (int) $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM whatif_master.temporadas")->fetchColumn();

echo "=== Importación batch La Liga ===\n\n";

foreach ($config['seasons'] as $season) {
    $seasonId = $season['season_id'];
    $dbName = $season['db'];
    $nombre = $season['nombre'];

    echo "=== Temporada $nombre (season_id=$seasonId, db=$dbName) ===\n";

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    foreach ($schemaStatements as $stmt) {
        if (!empty($stmt)) $pdo->exec($stmt);
    }
    echo "  DB preparada.\n";

    // Get all fixtures
    $allFixtureIds = [];
    $page = 1;
    do {
        $resp = apiRequest("/fixtures?filters=fixtureSeasons:$seasonId&per_page=25&page=$page");
        foreach ($resp['data'] ?? [] as $f) $allFixtureIds[] = $f['id'];
        $hasMore = $resp['pagination']['has_more'] ?? false;
        $page++;
        usleep(100000);
    } while ($hasMore);

    echo "  Fixtures: " . count($allFixtureIds) . "\n";

    // Prepare statements
    $stmtEq = $pdo->prepare('INSERT IGNORE INTO equipos (id, nombre, nombre_corto) VALUES (?, ?, ?)');
    $stmtPa = $pdo->prepare('INSERT IGNORE INTO partidos (id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, fecha, jornada) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmtJu = $pdo->prepare('INSERT IGNORE INTO jugadores (id, nombre, equipo_id, dorsal) VALUES (?, ?, ?, ?)');
    $stmtAl = $pdo->prepare('INSERT IGNORE INTO alineaciones (partido_id, jugador_id, equipo_id) VALUES (?, ?, ?)');
    $stmtEv = $pdo->prepare('INSERT IGNORE INTO eventos_partido (partido_id, jugador_id, asistente_id, equipo_id, tipo_evento, minuto) VALUES (?, ?, ?, ?, ?, ?)');

    $equipos = [];
    $procesados = 0;
    $total = count($allFixtureIds);

    foreach ($allFixtureIds as $fixtureId) {
        try {
            $resp = apiRequest("/fixtures/$fixtureId?include=events;lineups;scores;participants");
            $d = $resp['data'] ?? null;
            if (!$d) continue;
        } catch (Exception $e) {
            continue;
        }

        $localId = $visitanteId = null;
        foreach ($d['participants'] ?? [] as $p) {
            $pid = $p['id'];
            if (!isset($equipos[$pid])) {
                $equipos[$pid] = true;
                $stmtEq->execute([$pid, $p['name'], mb_substr($p['name'], 0, 20)]);
            }
            $loc = $p['meta']['location'] ?? '';
            if ($loc === 'home') $localId = $pid;
            if ($loc === 'away') $visitanteId = $pid;
        }
        if (!$localId || !$visitanteId) continue;

        $gL = $gV = 0;
        foreach ($d['scores'] ?? [] as $s) {
            if ($s['type_id'] == 1525) {
                if ($s['participant_id'] == $localId) $gL = $s['score']['goals'];
                elseif ($s['participant_id'] == $visitanteId) $gV = $s['score']['goals'];
            }
        }

        $stmtPa->execute([$fixtureId, $localId, $visitanteId, $gL, $gV, substr($d['starting_at'] ?? '', 0, 10), $d['round_id'] ?? 0]);

        foreach ($d['lineups'] ?? [] as $lu) {
            $jid = $lu['player_id'];
            $eid = $lu['team_id'] ?? $lu['participant_id'] ?? null;
            $stmtJu->execute([$jid, $lu['player_name'] ?? '', $eid, $lu['jersey_number'] ?? 0]);
            $stmtAl->execute([$fixtureId, $jid, $eid]);
        }

        foreach ($d['events'] ?? [] as $evt) {
            if (!in_array($evt['type_id'], [14, 15, 16])) continue;
            $tipo = $evt['type_id'] == 15 ? 'own_goal' : ($evt['type_id'] == 16 ? 'penalty' : 'goal');
            $stmtEv->execute([$fixtureId, $evt['player_id'], $evt['related_player_id'] ?? null, $evt['participant_id'] ?? 0, $tipo, $evt['minute']]);
        }

        $procesados++;
        if ($procesados % 50 == 0) {
            echo "  Procesados: $procesados/$total\n";
        }
        usleep(100000);
    }

    echo "  Procesados: $procesados/$total\n";

    // Calculate classification
    $contadores = [];
    foreach (array_keys($equipos) as $eqId) {
        $contadores[$eqId] = ['jugados' => 0, 'ganados' => 0, 'empatados' => 0, 'perdidos' => 0, 'gf' => 0, 'gc' => 0, 'puntos' => 0];
    }

    $rows = $pdo->query('SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante FROM partidos')->fetchAll(PDO::FETCH_OBJ);
    foreach ($rows as $r) {
        $L = $r->equipo_local_id; $V = $r->equipo_visitante_id;
        if (!isset($contadores[$L]) || !isset($contadores[$V])) continue;
        $contadores[$L]['jugados']++; $contadores[$V]['jugados']++;
        $contadores[$L]['gf'] += $r->goles_local; $contadores[$L]['gc'] += $r->goles_visitante;
        $contadores[$V]['gf'] += $r->goles_visitante; $contadores[$V]['gc'] += $r->goles_local;
        if ($r->goles_local > $r->goles_visitante) { $contadores[$L]['ganados']++; $contadores[$V]['perdidos']++; $contadores[$L]['puntos'] += 3; }
        elseif ($r->goles_local < $r->goles_visitante) { $contadores[$V]['ganados']++; $contadores[$L]['perdidos']++; $contadores[$V]['puntos'] += 3; }
        else { $contadores[$L]['empatados']++; $contadores[$V]['empatados']++; $contadores[$L]['puntos']++; $contadores[$V]['puntos']++; }
    }

    uasort($contadores, function ($a, $b) {
        if ($b['puntos'] !== $a['puntos']) return $b['puntos'] - $a['puntos'];
        return ($b['gf'] - $b['gc']) - ($a['gf'] - $a['gc']);
    });

    $stmtCl = $pdo->prepare('INSERT INTO clasificacion (equipo_id, posicion, jugados, ganados, empatados, perdidos, goles_favor, goles_contra, diferencia_goles, puntos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $pos = 1;
    foreach ($contadores as $eqId => $c) {
        $stmtCl->execute([$eqId, $pos++, $c['jugados'], $c['ganados'], $c['empatados'], $c['perdidos'], $c['gf'], $c['gc'], $c['gf'] - $c['gc'], $c['puntos']]);
    }

    // Show top 3
    $top = $pdo->query("SELECT e.nombre, c.puntos FROM clasificacion c JOIN equipos e ON e.id=c.equipo_id ORDER BY c.posicion LIMIT 3")->fetchAll(PDO::FETCH_OBJ);
    echo "  Top: ";
    foreach ($top as $t) echo "$t->nombre({$t->puntos}pts) ";
    echo "\n";

    // Register in master
    $pdo->exec("USE whatif_master");
    $pdo->exec("INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES ($nextTemporadaId, '$nombre', '$dbName', 0, {$config['liga_id']})");
    $nextTemporadaId++;

    // Stats
    $pdo->exec("USE `$dbName`");
    $stats = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM partidos) as p,
        (SELECT COUNT(*) FROM equipos) as e,
        (SELECT COUNT(*) FROM jugadores) as j,
        (SELECT COUNT(*) FROM eventos_partido) as ev,
        (SELECT COUNT(*) FROM alineaciones) as al")->fetch(PDO::FETCH_OBJ);
    echo "  Stats: {$stats->p} partidos, {$stats->e} equipos, {$stats->j} jugadores, {$stats->ev} eventos, {$stats->al} alineaciones\n\n";
}

echo "=== Batch completado ===\n";
echo "Temporadas añadidas: " . count($config['seasons']) . "\n";
