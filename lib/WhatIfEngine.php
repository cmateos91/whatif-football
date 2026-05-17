<?php

class WhatIfEngine
{
    public function calcularSinJugador(int $jugadorId): array
    {
        //1.Obtener jugador
        $jugador = Doctrine_Core::getTable('Jugador')->find($jugadorId);
        if (!$jugador) {
            throw new Exception('Jugador no encontrado');
        }

        //2.Partidos en los que jugó
        $alineaciones = AlineacionTable::getInstance()->findPartidosPorJugador($jugadorId);
        $partidoIds = [];
        foreach ($alineaciones as $a) {
            $partidoIds[] = $a->partido_id;
        }

        if (empty($partidoIds)) {
            throw new Exception("El jugador no jugó ningún partido");
        }

        //3.Goles del jugador agrupados por partido
        $eventos = EventoPartidoTable::getInstance()->findGolesPorJugadorEnPartidos($jugadorId, $partidoIds);
        $golesPorPartido = [];
        foreach ($eventos as $e) {
            $golesPorPartido[$e->partido_id] = ($golesPorPartido[$e->partido_id] ?? 0) + 1;
        }

        //4.Todos los partidos de la temporada
        $todosPartidos = PartidoTable::getInstance()->findTodos();

        //5.Recalcular resultados y acumular contadores
        $contadores = [];
        $partidosAfectados = [];

        foreach ($todosPartidos as $partido) {
            $localId = $partido->equipo_local_id;
            $visitanteId = $partido->equipo_visitante_id;

            if (!isset($contadores[$localId])) $contadores[$localId] = $this->contadorVacio($localId);
            if (!isset($contadores[$visitanteId])) $contadores[$visitanteId] = $this->contadorVacio($visitanteId);

            $golesQuitados = $golesPorPartido[$partido->id] ?? 0;
            $nuevaLocal = $partido->goles_local;
            $nuevaVisitante = $partido->goles_visitante;

            if ($golesQuitados > 0) {
                if ($localId === $jugador->equipo_id) {
                    $nuevaLocal = max(0, $partido->goles_local - $golesQuitados);
                } else {
                    $nuevaVisitante = max(0, $partido->goles_visitante - $golesQuitados);
                }

                $resultadoOrig = $partido->goles_local <=> $partido->goles_visitante;
                $resultadoNuevo = $nuevaLocal <=> $nuevaVisitante;

                if ($resultadoOrig !== $resultadoNuevo) {
                    $partidosAfectados[] = [
                        'jornada' => $partido->jornada,
                        'local' => $partido->EquipoLocal->nombre_corto,
                        'visitante' => $partido->EquipoVisitante->nombre_corto,
                        'resultado_orig' => $partido->goles_local . '-' . $partido->goles_visitante,
                        'resultado_nuevo' => $nuevaLocal . '-' . $nuevaVisitante,
                        'goles_quitados' => $golesQuitados,
                    ];
                }
            }

            $this->actualizarContador($contadores[$localId], $nuevaLocal, $nuevaVisitante);
            $this->actualizarContador($contadores[$visitanteId], $nuevaVisitante, $nuevaLocal);
        }

        //6.Clasificación original
        $clasOriginal = ClasificacionTable::getInstance()->findOrdenada();
        $original = [];
        foreach ($clasOriginal as $fila) {
            $original[] = [
                'posicion' => $fila->posicion,
                'equipo' => $fila->Equipo->nombre_corto,
                'puntos' => $fila->puntos,
                'gf' => $fila->goles_favor,
                'gc' => $fila->goles_contra,
            ];
        }

        $posicionesOriginales = [];
        foreach ($original as $fila) {
            $posicionesOriginales[$fila['equipo']] = $fila['posicion'];
        }

        //7.Nueva clasificación
        $nueva = array_values($contadores);
        usort($nueva, function ($a, $b) {
            if ($b['puntos'] !== $a['puntos']) return $b['puntos'] - $a['puntos'];
            $gdA = $a['gf'] - $a['gc'];
            $gdB = $b['gf'] - $b['gc'];
            if ($gdB !== $gdA) return $gdB - $gdA;
        });

        $equiposMap = [];
        foreach (EquipoTable::getInstance()->findTodos() as $e) {
            $equiposMap[$e->id] = $e->nombre_corto;
        }

        $nuevaClasificacion = [];
        foreach ($nueva as $i => $fila) {
            $posOrig = $posicionesOriginales[$equiposMap[$fila['equipo_id']]] ?? $i + 1;
            $cambio = $posOrig - ($i + 1);
            $nuevaClasificacion[] = [
                'posicion' => $i + 1,
                'equipo' => $equiposMap[$fila['equipo_id']],
                'puntos' => $fila['puntos'],
                'gf' => $fila['gf'],
                'gc' => $fila['gc'],
                'cambio_posicion' => $cambio,
            ];
        }

        return [
            'jugador' => $jugador->nombre,
            'equipo' => $jugador->Equipo->nombre,
            'total_goles' => array_sum($golesPorPartido),
            'partidos_jugados' => count($alineaciones),
            'original' => $original,
            'nueva' => $nuevaClasificacion,
            'partidos_afectados' => $partidosAfectados,
        ];
    }

    private function contadorVacio(int $equipoId): array
    {
        return [
            'equipo_id' => $equipoId,
            'jugados' => 0,
            'ganados' => 0,
            'empatados' => 0,
            'perdidos' => 0,
            'puntos' => 0,
            'gf' => 0,
            'gc' => 0,
        ];
    }

    private function actualizarContador(array &$c, int $golesFavor, int $golesContra)
    {
        $c['jugados']++;
        $c['gf'] += $golesFavor;
        $c['gc'] += $golesContra;

        if ($golesFavor > $golesContra) {
            $c['ganados']++;
            $c['puntos'] += 3;
        } elseif ($golesFavor === $golesContra) {
            $c['empatados']++;
            $c['puntos']++;
        } else {
            $c['perdidos']++;
        }
    }
}
