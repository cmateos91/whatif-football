<?php

class MainController extends ApplicationController
{
    public function index()
    {
        //Temporadas disponibles desde BDMaster
        $conn = Doctrine_Manager::getInstance()->getConnection('master');
        $stmt = $conn->execute('SELECT id, nombre FROM temporadas ORDER BY id ASC');
        $temporadas = $stmt->fetchAll(PDO::FETCH_OBJ);

        $jugadores = JugadorTable::getInstance()->findConGoles();

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
            'temporadas' => $temporadas,
            'temporada_actual_id' => $_SESSION['temporada_id'],
            'temporada_actual_nombre' => $_SESSION['temporada_nombre'],
        ]);
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
