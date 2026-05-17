<?php
require_once(__DIR__ . '/../init.php');

$ligas = [
    'es' => ['competition_id' => 11, 'season_id' => 27, 'db' => 'whatif_es1516'],
    'en' => ['competition_id' => 2,  'season_id' => 27, 'db' => 'whatif_en1516'],
    'it' => ['competition_id' => 12, 'season_id' => 27, 'db' => 'whatif_it1516'],
    'fr' => ['competition_id' => 7,  'season_id' => 27, 'db' => 'whatif_fr1516'],
    'de' => ['competition_id' => 9,  'season_id' => 27, 'db' => 'whatif_de1516'],
];

$codigo = $argv[1] ?? null;
if (!$codigo || !isset($ligas[$codigo])) {
    die("Uso: php import_statsbomb.php [es|en|it|fr|de]\n");
}

$liga = $ligas[$codigo];
$statsbombPath = '/tmp/open-data/data';

// Conectar a la BD de la liga
$pdo = new PDO(
    'mysql:host=localhost;dbname=' . $liga['db'] . ';charset=utf8mb4',
    'root',
    'root'
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Importando {$liga['db']}...\n";

// 1. Leer partidos
$matchesFile = "{$statsbombPath}/matches/{$liga['competition_id']}/{$liga['season_id']}.json";
$partidos = json_decode(file_get_contents($matchesFile), true);
echo "Partidos encontrados: " . count($partidos) . "\n";

// 2. Insertar equipos (únicos)
$equipos = [];
foreach ($partidos as $p) {
    $equipos[$p['home_team']['home_team_id']] = $p['home_team']['home_team_name'];
    $equipos[$p['away_team']['away_team_id']] = $p['away_team']['away_team_name'];
}

$stmtEquipo = $pdo->prepare('INSERT INTO equipos (id, nombre, nombre_corto) VALUES (?, ?, ?)');
foreach ($equipos as $id => $nombre) {
    $corto = substr($nombre, 0, 20);
    $stmtEquipo->execute([$id, $nombre, $corto]);
}
echo "Equipos insertados: " . count($equipos) . "\n";

// 3. Insertar partidos, jugadores, alineaciones y goles
$jugadoresInsertados = [];
$stmtPartido   = $pdo->prepare('INSERT INTO partidos (id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, fecha, jornada) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmtJugador   = $pdo->prepare('INSERT INTO jugadores (id, nombre, equipo_id, dorsal) VALUES (?, ?, ?, ?)');
$stmtAlineacion = $pdo->prepare('INSERT INTO alineaciones (partido_id, jugador_id, equipo_id) VALUES (?, ?, ?)');
$stmtEvento    = $pdo->prepare('INSERT INTO eventos_partido (partido_id, jugador_id, equipo_id, tipo_evento, minuto) VALUES (?, ?, ?, ?, ?)');

foreach ($partidos as $p) {
    $matchId   = $p['match_id'];
    $localId   = $p['home_team']['home_team_id'];
    $visitanteId = $p['away_team']['away_team_id'];

    $stmtPartido->execute([
        $matchId,
        $localId,
        $visitanteId,
        $p['home_score'],
        $p['away_score'],
        $p['match_date'],
        $p['match_week'],
    ]);

    // Alineaciones
    $lineupFile = "{$statsbombPath}/lineups/{$matchId}.json";
    $lineups = json_decode(file_get_contents($lineupFile), true);

    foreach ($lineups as $equipo) {
        $equipoId = $equipo['team_id'];
        foreach ($equipo['lineup'] as $jugador) {
            $jugadorId = $jugador['player_id'];
            if (!isset($jugadoresInsertados[$jugadorId])) {
                $stmtJugador->execute([
                    $jugadorId,
                    $jugador['player_name'],
                    $equipoId,
                    $jugador['jersey_number'],
                ]);
                $jugadoresInsertados[$jugadorId] = true;
            }
            $stmtAlineacion->execute([$matchId, $jugadorId, $equipoId]);
        }
    }

    // Goles
    $eventsFile = "{$statsbombPath}/events/{$matchId}.json";
    $eventos = json_decode(file_get_contents($eventsFile), true);

    foreach ($eventos as $e) {
        if ($e['type']['name'] !== 'Shot') continue;
        if (($e['shot']['outcome']['name'] ?? '') !== 'Goal') continue;

        $stmtEvento->execute([
            $matchId,
            $e['player']['id'],
            $e['team']['id'],
            'goal',
            $e['minute'],
        ]);
    }
}

echo "Jugadores insertados: " . count($jugadoresInsertados) . "\n";

// 4. Calcular y guardar clasificación
echo "Calculando clasificación...\n";
$contadores = [];
foreach (array_keys($equipos) as $eqId) {
    $contadores[$eqId] = ['jugados' => 0, 'ganados' => 0, 'empatados' => 0, 'perdidos' => 0, 'gf' => 0, 'gc' => 0, 'puntos' => 0];
}

$rows = $pdo->query('SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante FROM partidos')->fetchAll(PDO::FETCH_OBJ);
foreach ($rows as $r) {
    $contadores[$r->equipo_local_id]['jugados']++;
    $contadores[$r->equipo_visitante_id]['jugados']++;
    $contadores[$r->equipo_local_id]['gf'] += $r->goles_local;
    $contadores[$r->equipo_local_id]['gc'] += $r->goles_visitante;
    $contadores[$r->equipo_visitante_id]['gf'] += $r->goles_visitante;
    $contadores[$r->equipo_visitante_id]['gc'] += $r->goles_local;

    if ($r->goles_local > $r->goles_visitante) {
        $contadores[$r->equipo_local_id]['ganados']++;
        $contadores[$r->equipo_visitante_id]['perdidos']++;
        $contadores[$r->equipo_local_id]['puntos'] += 3;
    } elseif ($r->goles_local < $r->goles_visitante) {
        $contadores[$r->equipo_visitante_id]['ganados']++;
        $contadores[$r->equipo_local_id]['perdidos']++;
        $contadores[$r->equipo_visitante_id]['puntos'] += 3;
    } else {
        $contadores[$r->equipo_local_id]['empatados']++;
        $contadores[$r->equipo_visitante_id]['empatados']++;
        $contadores[$r->equipo_local_id]['puntos']++;
        $contadores[$r->equipo_visitante_id]['puntos']++;
    }
}

uasort($contadores, function ($a, $b) {
    if ($b['puntos'] !== $a['puntos']) return $b['puntos'] - $a['puntos'];
    $gdA = $a['gf'] - $a['gc'];
    $gdB = $b['gf'] - $b['gc'];
    return $gdB - $gdA;
});

$stmtClas = $pdo->prepare('INSERT INTO clasificacion (equipo_id, posicion, jugados, ganados, empatados, perdidos, goles_favor, goles_contra, diferencia_goles, puntos) VALUES (?, ?,
   ?, ?, ?, ?, ?, ?, ?, ?)');
$pos = 1;
foreach ($contadores as $eqId => $c) {
    $stmtClas->execute([
        $eqId,
        $pos++,
        $c['jugados'],
        $c['ganados'],
        $c['empatados'],
        $c['perdidos'],
        $c['gf'],
        $c['gc'],
        $c['gf'] - $c['gc'],
        $c['puntos'],
    ]);
}

echo "Importación completada.\n";
