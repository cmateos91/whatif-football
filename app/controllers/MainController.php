<?php

class MainController extends ApplicationController
{
    public function index()
    {
        $conn = Doctrine_Manager::getInstance()->getConnection('master');

        $stmtLigas = $conn->execute('SELECT id, nombre FROM ligas ORDER BY id ASC');
        $ligas = $stmtLigas->fetchAll(PDO::FETCH_OBJ);

        // Temporada actual y su liga
        $stmtActual = $conn->execute(
            'SELECT t.id, t.nombre, t.liga_id FROM temporadas t WHERE t.id = ? LIMIT 1',
            [$_SESSION['temporada_id']]
        );
        $temporadaActual = $stmtActual->fetch(PDO::FETCH_OBJ);

        // Temporadas de la liga activa
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

        $this->render('main/index.tpl', [
            'jugadores_por_equipo' => $porEquipo,
            'ligas'                => $ligas,
            'temporadas'           => $temporadas,
            'liga_actual_id'       => $temporadaActual->liga_id,
            'temporada_actual_id'  => $_SESSION['temporada_id'],
        ]);
    }

    public function temporadasPorLiga()
    {
        $ligaId = isset($_GET['liga_id']) ? (int) $_GET['liga_id'] : 0;
        if (!$ligaId) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $conn = Doctrine_Manager::getInstance()->getConnection('master');
        $stmt = $conn->execute(
            'SELECT id, nombre FROM temporadas WHERE liga_id = ? ORDER BY id ASC',
            [$ligaId]
        );
        $temporadas = $stmt->fetchAll(PDO::FETCH_OBJ);

        header('Content-Type: application/json');
        echo json_encode($temporadas);
    }

    public function cambiarTemporada()
    {
        $temporadaId = isset($_POST['temporada_id']) ? (int) $_POST['temporada_id'] : 0;
        if ($temporadaId) {
            $_SESSION['temporada_id'] = $temporadaId;
        }
        header('Location: /');
        exit;
    }
}
