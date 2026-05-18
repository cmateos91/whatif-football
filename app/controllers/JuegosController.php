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
        $jugador1Id = isset($_POST['jugador1_id']) ? (int) $_POST['jugador1_id'] : 0;
        $jugador2Id = isset($_POST['jugador2_id']) ? (int) $_POST['jugador2_id'] : 0;

        if (!$jugador1Id || !$jugador2Id || $jugador1Id === $jugador2Id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Selecciona dos jugadores distintos']);
            return;
        }

        try {
            require_once(__DIR__ . '/../../lib/WhatIfEngine.php');
            $engine = new WhatIfEngine();

            $resultado1 = $engine->calcularSinJugador($jugador1Id, 'ambos');
            $resultado2 = $engine->calcularSinJugador($jugador2Id, 'ambos');

            $pts1 = array_sum(array_map(fn($p) => $p['puntos_orig'] - $p['puntos_nuevo'], $resultado1['partidos_afectados']));
            $pts2 = array_sum(array_map(fn($p) => $p['puntos_orig'] - $p['puntos_nuevo'], $resultado2['partidos_afectados']));

            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'jugador1' => [
                    'nombre'           => $resultado1['jugador'],
                    'equipo'           => $resultado1['equipo'],
                    'goles'            => $resultado1['total_goles'],
                    'asistencias'      => $resultado1['total_asistencias'],
                    'partidos'         => $resultado1['partidos_jugados'],
                    'pts_aportados'    => $pts1,
                    'partidos_clave'   => count($resultado1['partidos_afectados']),
                ],
                'jugador2' => [
                    'nombre'           => $resultado2['jugador'],
                    'equipo'           => $resultado2['equipo'],
                    'goles'            => $resultado2['total_goles'],
                    'asistencias'      => $resultado2['total_asistencias'],
                    'partidos'         => $resultado2['partidos_jugados'],
                    'pts_aportados'    => $pts2,
                    'partidos_clave'   => count($resultado2['partidos_afectados']),
                ],
                'ganador' => $pts1 > $pts2 ? 1 : ($pts2 > $pts1 ? 2 : 0),
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
