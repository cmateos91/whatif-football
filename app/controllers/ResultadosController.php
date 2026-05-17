<?php

class ResultadosController extends ApplicationController
{
    public function calcular()
    {
        $jugadorId = isset($_POST['jugador_id']) ? (int) $_POST['jugador_id'] : 0;
        $modo = isset($_POST['modo']) && in_array($_POST['modo'], ['ambos', 'goles', 'asistencias'])
            ? $_POST['modo']
            : 'ambos';

        if (!$jugadorId) {
            $this->jsonError('Jugador no válido');
            return;
        }

        try {
            require_once(__DIR__ . '/../../lib/WhatIfEngine.php');
            $engine = new WhatIfEngine();
            $resultado = $engine->calcularSinJugador($jugadorId, $modo);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'data' => $resultado]);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function jsonError(string $mensaje)
    {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $mensaje]);
    }
}
