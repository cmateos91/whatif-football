<?php

class JuegosController extends ApplicationController
{
    public function caraacara()
    {
        $conn = Doctrine_Manager::getInstance()->getConnection('master');

        $stmtLigas = $conn->execute('SELECT id, nombre FROM ligas ORDER BY id ASC');
        $ligas = $stmtLigas->fetchAll(PDO::FETCH_OBJ);

        $stmtActual = $conn->execute(
            'SELECT t.id, t.nombre, t.liga_id FROM temporadas t WHERE t.id = ? LIMIT 1',
            [$_SESSION['temporada_id']]
        );
        $temporadaActual = $stmtActual->fetch(PDO::FETCH_OBJ);

        $stmtTemporadas = $conn->execute(
            'SELECT id, nombre FROM temporadas WHERE liga_id = ? ORDER BY id ASC',
            [$temporadaActual->liga_id]
        );
        $temporadas = $stmtTemporadas->fetchAll(PDO::FETCH_OBJ);

        $jugadores = JugadorTable::getInstance()->findConGolesOAsistencias();

        $porEquipo = [];
        foreach ($jugadores as $j) {
            $equipoNombre = $j->Equipo->nombre;
            if (!isset($porEquipo[$equipoNombre])) {
                $porEquipo[$equipoNombre] = [];
            }
            $porEquipo[$equipoNombre][] = $j;
        }

        $this->render('juegos/cara-a-cara.tpl', [
            'jugadores_por_equipo' => $porEquipo,
            'ligas'                => $ligas,
            'temporadas'           => $temporadas,
            'liga_actual_id'       => $temporadaActual->liga_id,
            'temporada_actual_id'  => $_SESSION['temporada_id'],
            'current_page'         => 'juegos',
            'extra_js'             => '/public/js/cara-a-cara.js',
        ]);
    }

    public function comparar()
    {
        header('Content-Type: application/json');

        $jugador1Id = isset($_POST['jugador1_id']) ? (int) $_POST['jugador1_id'] : 0;
        $jugador2Id = isset($_POST['jugador2_id']) ? (int) $_POST['jugador2_id'] : 0;

        if (!$jugador1Id || !$jugador2Id || $jugador1Id === $jugador2Id) {
            echo json_encode(['ok' => false, 'error' => 'Selecciona dos jugadores distintos']);
            return;
        }

        try {
            require_once(__DIR__ . '/../../lib/WhatIfEngine.php');
            $engine = new WhatIfEngine();

            $r1 = $engine->calcularSinJugador($jugador1Id, 'ambos');
            $r2 = $engine->calcularSinJugador($jugador2Id, 'ambos');

            $pts1 = array_sum(array_map(function ($p) { return $p['puntos_orig'] - $p['puntos_nuevo']; }, $r1['partidos_afectados']));
            $pts2 = array_sum(array_map(function ($p) { return $p['puntos_orig'] - $p['puntos_nuevo']; }, $r2['partidos_afectados']));

            echo json_encode([
                'ok'      => true,
                'jugador1' => $this->formatJugador($r1, $pts1),
                'jugador2' => $this->formatJugador($r2, $pts2),
                'ganador'  => $pts1 > $pts2 ? 1 : ($pts2 > $pts1 ? 2 : 0),
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function parAleatorio()
    {
        header('Content-Type: application/json');

        try {
            require_once(__DIR__ . '/../../lib/WhatIfEngine.php');
            $engine = new WhatIfEngine();

            $jugadores = JugadorTable::getInstance()->findConGolesOAsistencias();
            $lista = [];
            foreach ($jugadores as $j) {
                $lista[] = $j->id;
            }

            if (count($lista) < 2) {
                echo json_encode(['ok' => false, 'error' => 'No hay suficientes jugadores']);
                return;
            }

            $keys = array_rand($lista, 2);
            $r1 = $engine->calcularSinJugador($lista[$keys[0]], 'ambos');
            $r2 = $engine->calcularSinJugador($lista[$keys[1]], 'ambos');

            $pts1 = array_sum(array_map(function ($p) { return $p['puntos_orig'] - $p['puntos_nuevo']; }, $r1['partidos_afectados']));
            $pts2 = array_sum(array_map(function ($p) { return $p['puntos_orig'] - $p['puntos_nuevo']; }, $r2['partidos_afectados']));

            echo json_encode([
                'ok'      => true,
                'jugador1' => $this->formatJugador($r1, $pts1),
                'jugador2' => $this->formatJugador($r2, $pts2),
                'ganador'  => $pts1 > $pts2 ? 1 : ($pts2 > $pts1 ? 2 : 0),
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function guardarPuntuacion()
    {
        header('Content-Type: application/json');

        $nombre    = isset($_POST['nombre']) ? trim(strip_tags($_POST['nombre'])) : '';
        $aciertos  = isset($_POST['aciertos']) ? (int) $_POST['aciertos'] : 0;
        $tiempo    = isset($_POST['tiempo_segundos']) ? (int) $_POST['tiempo_segundos'] : 0;
        $temporada = $_SESSION['temporada_id'];

        if ($nombre === '' || $aciertos < 1) {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            return;
        }

        $nombre = mb_substr($nombre, 0, 100);

        $conn = Doctrine_Manager::getInstance()->getConnection('master');
        $conn->execute(
            'INSERT INTO puntuaciones (nombre, aciertos, tiempo_segundos, temporada_id) VALUES (?, ?, ?, ?)',
            [$nombre, $aciertos, $tiempo, $temporada]
        );

        echo json_encode(['ok' => true]);
    }

    public function clasificacion()
    {
        header('Content-Type: application/json');

        $temporada = $_SESSION['temporada_id'];
        $conn = Doctrine_Manager::getInstance()->getConnection('master');
        $stmt = $conn->execute(
            'SELECT nombre, aciertos, tiempo_segundos FROM puntuaciones
             WHERE temporada_id = ?
             ORDER BY aciertos DESC, tiempo_segundos ASC
             LIMIT 10',
            [$temporada]
        );

        $top10 = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['ok' => true, 'clasificacion' => $top10]);
    }

    private function formatJugador(array $resultado, int $pts): array
    {
        return [
            'nombre'         => $resultado['jugador'],
            'equipo'         => $resultado['equipo'],
            'goles'          => $resultado['total_goles'],
            'asistencias'    => $resultado['total_asistencias'],
            'partidos'       => $resultado['partidos_jugados'],
            'pts_aportados'  => $pts,
            'partidos_clave' => count($resultado['partidos_afectados']),
        ];
    }
}
