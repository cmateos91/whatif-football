<?php
$codigo = $argv[1] ?? null;

$batchConfigs = [
    'en' => [
        'league_id' => 8,
        'liga_nombre' => 'Premier League',
        'liga_id' => 2,
        'code' => 'en',
        'seasons' => [
            ['season_id' => 13,    'db' => 'whatif_en1617', 'nombre' => '16/17'],
            ['season_id' => 6397,  'db' => 'whatif_en1718', 'nombre' => '17/18'],
            ['season_id' => 12962, 'db' => 'whatif_en1819', 'nombre' => '18/19'],
            ['season_id' => 16036, 'db' => 'whatif_en1920', 'nombre' => '19/20'],
            ['season_id' => 17420, 'db' => 'whatif_en2021', 'nombre' => '20/21'],
            ['season_id' => 18378, 'db' => 'whatif_en2122', 'nombre' => '21/22'],
            ['season_id' => 19734, 'db' => 'whatif_en2223', 'nombre' => '22/23'],
            ['season_id' => 21646, 'db' => 'whatif_en2324', 'nombre' => '23/24'],
            ['season_id' => 23614, 'db' => 'whatif_en2425', 'nombre' => '24/25'],
        ],
    ],
    'it' => [
        'league_id' => 384,
        'liga_nombre' => 'Serie A',
        'liga_id' => 3,
        'code' => 'it',
        'seasons' => [
            ['season_id' => 802,   'db' => 'whatif_it1617', 'nombre' => '16/17'],
            ['season_id' => 8557,  'db' => 'whatif_it1718', 'nombre' => '17/18'],
            ['season_id' => 13158, 'db' => 'whatif_it1819', 'nombre' => '18/19'],
            ['season_id' => 16415, 'db' => 'whatif_it1920', 'nombre' => '19/20'],
            ['season_id' => 17488, 'db' => 'whatif_it2021', 'nombre' => '20/21'],
            ['season_id' => 18576, 'db' => 'whatif_it2122', 'nombre' => '21/22'],
            ['season_id' => 19806, 'db' => 'whatif_it2223', 'nombre' => '22/23'],
            ['season_id' => 21818, 'db' => 'whatif_it2324', 'nombre' => '23/24'],
            ['season_id' => 23746, 'db' => 'whatif_it2425', 'nombre' => '24/25'],
        ],
    ],
    'fr' => [
        'league_id' => 301,
        'liga_nombre' => 'Ligue 1',
        'liga_id' => 4,
        'code' => 'fr',
        'seasons' => [
            ['season_id' => 765,   'db' => 'whatif_fr1617', 'nombre' => '16/17'],
            ['season_id' => 6405,  'db' => 'whatif_fr1718', 'nombre' => '17/18'],
            ['season_id' => 12935, 'db' => 'whatif_fr1819', 'nombre' => '18/19'],
            ['season_id' => 16043, 'db' => 'whatif_fr1920', 'nombre' => '19/20'],
            ['season_id' => 17160, 'db' => 'whatif_fr2021', 'nombre' => '20/21'],
            ['season_id' => 18441, 'db' => 'whatif_fr2122', 'nombre' => '21/22'],
            ['season_id' => 19745, 'db' => 'whatif_fr2223', 'nombre' => '22/23'],
            ['season_id' => 21779, 'db' => 'whatif_fr2324', 'nombre' => '23/24'],
            ['season_id' => 23643, 'db' => 'whatif_fr2425', 'nombre' => '24/25'],
        ],
    ],
    'de' => [
        'league_id' => 82,
        'liga_nombre' => 'Bundesliga',
        'liga_id' => 5,
        'code' => 'de',
        'seasons' => [
            ['season_id' => 219,   'db' => 'whatif_de1617', 'nombre' => '16/17'],
            ['season_id' => 8026,  'db' => 'whatif_de1718', 'nombre' => '17/18'],
            ['season_id' => 13005, 'db' => 'whatif_de1819', 'nombre' => '18/19'],
            ['season_id' => 16264, 'db' => 'whatif_de1920', 'nombre' => '19/20'],
            ['season_id' => 17361, 'db' => 'whatif_de2021', 'nombre' => '20/21'],
            ['season_id' => 18444, 'db' => 'whatif_de2122', 'nombre' => '21/22'],
            ['season_id' => 19744, 'db' => 'whatif_de2223', 'nombre' => '22/23'],
            ['season_id' => 21795, 'db' => 'whatif_de2324', 'nombre' => '23/24'],
            ['season_id' => 23744, 'db' => 'whatif_de2425', 'nombre' => '24/25'],
        ],
    ],
];

if (!$codigo || !isset($batchConfigs[$codigo])) {
    $keys = implode('|', array_keys($batchConfigs));
    die("Uso: php import_sportmonks_all.php [$keys]\n");
}

$config = $batchConfigs[$codigo];

$apiKeyFile = __DIR__ . '/../apisportmonks.txt';
$apiKey = trim(file_get_contents($apiKeyFile));
$baseUrl = 'https://api.sportmonks.com/v3/football';

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'root';

function apiReq($url) {
    global $baseUrl, $apiKey;
    $sep = strpos($url, '?') === false ? '?' : '&';
    $ch = curl_init($baseUrl . $url . $sep . 'api_token=' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $r = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($r, true);
    if (isset($d['rate_limit'])) {
        printf("  [API:%d]\n", $d['rate_limit']['remaining'] ?? 0);
    }
    return $d;
}

$pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schemaFile = __DIR__ . '/schema.sql';
$schema = file_get_contents($schemaFile);
$schemaStatements = array_filter(array_map('trim', explode(';', $schema)));

$nextTemporadaId = (int) $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM whatif_master.temporadas")->fetchColumn();

echo "=== {$config['liga_nombre']} ===\n\n";

foreach ($config['seasons'] as $season) {
    $sid = $season['season_id'];
    $db = $season['db'];
    $name = $season['nombre'];
    echo "=== $name (S:$sid, DB:$db) ===\n";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    foreach ($schemaStatements as $st) { if (!empty($st)) $pdo->exec($st); }
    echo "  DB ok\n";

    $allIds = [];
    $page = 1;
    do {
        $r = apiReq("/fixtures?filters=fixtureSeasons:$sid&per_page=25&page=$page");
        foreach ($r['data'] ?? [] as $f) $allIds[] = $f['id'];
        $more = $r['pagination']['has_more'] ?? false;
        $page++;
        usleep(100000);
    } while ($more);
    echo "  Fixtures: " . count($allIds) . "\n";

    $stmtEq = $pdo->prepare('INSERT IGNORE INTO equipos (id, nombre, nombre_corto) VALUES (?, ?, ?)');
    $stmtPa = $pdo->prepare('INSERT IGNORE INTO partidos (id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, fecha, jornada) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmtJu = $pdo->prepare('INSERT IGNORE INTO jugadores (id, nombre, equipo_id, dorsal) VALUES (?, ?, ?, ?)');
    $stmtAl = $pdo->prepare('INSERT IGNORE INTO alineaciones (partido_id, jugador_id, equipo_id) VALUES (?, ?, ?)');
    $stmtEv = $pdo->prepare('INSERT IGNORE INTO eventos_partido (partido_id, jugador_id, asistente_id, equipo_id, tipo_evento, minuto) VALUES (?, ?, ?, ?, ?, ?)');

    $proc = 0;
    foreach ($allIds as $fid) {
        $d = apiReq("/fixtures/$fid?include=events;lineups;scores;participants")['data'] ?? null;
        if (!$d) continue;

        $L = $V = null;
        foreach ($d['participants'] ?? [] as $p) {
            $stmtEq->execute([$p['id'], $p['name'], mb_substr($p['name'], 0, 20)]);
            if (($p['meta']['location'] ?? '') === 'home') $L = $p['id'];
            if (($p['meta']['location'] ?? '') === 'away') $V = $p['id'];
        }
        if (!$L || !$V) continue;

        $gL = $gV = 0;
        foreach ($d['scores'] ?? [] as $s) {
            if ($s['type_id'] == 1525) {
                if ($s['participant_id'] == $L) $gL = $s['score']['goals'];
                elseif ($s['participant_id'] == $V) $gV = $s['score']['goals'];
            }
        }
        $stmtPa->execute([$fid, $L, $V, $gL, $gV, substr($d['starting_at'] ?? '', 0, 10), $d['round_id'] ?? 0]);

        foreach ($d['lineups'] ?? [] as $lu) {
            $eid = $lu['team_id'] ?? $lu['participant_id'] ?? 0;
            $stmtJu->execute([$lu['player_id'], $lu['player_name'] ?? '', $eid, $lu['jersey_number'] ?? 0]);
            $stmtAl->execute([$fid, $lu['player_id'], $eid]);
        }

        foreach ($d['events'] ?? [] as $evt) {
            if (!in_array($evt['type_id'], [14, 15, 16])) continue;
            $t = $evt['type_id'] == 15 ? 'own_goal' : ($evt['type_id'] == 16 ? 'penalty' : 'goal');
            $stmtEv->execute([$fid, $evt['player_id'], $evt['related_player_id'] ?? null, $evt['participant_id'] ?? 0, $t, $evt['minute']]);
        }
        $proc++;
        if ($proc % 50 == 0) echo "  $proc/" . count($allIds) . "\n";
        usleep(100000);
    }
    echo "  $proc done\n";

    // Classification
    $pdo->exec('DELETE FROM clasificacion');
    $eqs = $pdo->query('SELECT id FROM equipos')->fetchAll(PDO::FETCH_COLUMN);
    $cnt = [];
    foreach ($eqs as $e) $cnt[$e] = ['j' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'gc' => 0, 'pt' => 0];
    $rs = $pdo->query('SELECT equipo_local_id, equipo_visitante_id, goles_local, goles_visitante FROM partidos')->fetchAll(PDO::FETCH_OBJ);
    foreach ($rs as $r) {
        $L = $r->equipo_local_id; $V = $r->equipo_visitante_id;
        if (!isset($cnt[$L]) || !isset($cnt[$V])) continue;
        $cnt[$L]['j']++; $cnt[$V]['j']++;
        $cnt[$L]['gf'] += $r->goles_local; $cnt[$L]['gc'] += $r->goles_visitante;
        $cnt[$V]['gf'] += $r->goles_visitante; $cnt[$V]['gc'] += $r->goles_local;
        if ($r->goles_local > $r->goles_visitante) { $cnt[$L]['w']++; $cnt[$V]['l']++; $cnt[$L]['pt'] += 3; }
        elseif ($r->goles_local < $r->goles_visitante) { $cnt[$V]['w']++; $cnt[$L]['l']++; $cnt[$V]['pt'] += 3; }
        else { $cnt[$L]['d']++; $cnt[$V]['d']++; $cnt[$L]['pt']++; $cnt[$V]['pt']++; }
    }
    uasort($cnt, function ($a, $b) { if ($b['pt'] !== $a['pt']) return $b['pt'] - $a['pt']; return ($b['gf'] - $b['gc']) - ($a['gf'] - $a['gc']); });
    $stmtC = $pdo->prepare('INSERT INTO clasificacion (equipo_id, posicion, jugados, ganados, empatados, perdidos, goles_favor, goles_contra, diferencia_goles, puntos) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $pos = 1;
    foreach ($cnt as $eq => $c) $stmtC->execute([$eq, $pos++, $c['j'], $c['w'], $c['d'], $c['l'], $c['gf'], $c['gc'], $c['gf'] - $c['gc'], $c['pt']]);

    $top = $pdo->query("SELECT e.nombre, c.puntos FROM clasificacion c JOIN equipos e ON e.id=c.equipo_id ORDER BY c.posicion LIMIT 3")->fetchAll(PDO::FETCH_OBJ);
    echo "  ";
    foreach ($top as $t) echo "$t->nombre({$t->puntos}) ";
    echo "\n";

    $st = $pdo->query("SELECT (SELECT COUNT(*) FROM partidos) as p, (SELECT COUNT(*) FROM equipos) as e, (SELECT COUNT(*) FROM eventos_partido) as ev")->fetch(PDO::FETCH_OBJ);
    echo "  {$st->p}P {$st->e}E {$st->ev}EV\n";

    // Master
    $pdo->exec("USE whatif_master");
    $pdo->exec("INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES ($nextTemporadaId, '$name', '$db', 0, {$config['liga_id']})");
    $nextTemporadaId++;
    $pdo->exec("USE `$db`");
    echo "\n";
}

echo "=== {$config['liga_nombre']} completado ===\n";
