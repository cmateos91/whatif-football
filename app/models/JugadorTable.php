<?php

class JugadorTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Jugador');
    }

    public function findconGoles()
    {
        return $this->createQuery('j')
            ->innerJoin('j.Equipo e')
            ->where('j.id IN (SELECT ev.jugador_id FROM EventoPartido ev WHERE ev.tipo_evento = ?)', 'goal')
            ->orderBy('e.nombre ASC, j.nombre ASC')
            ->execute();
    }

    public function findConGolesOAsistencias()
    {
        return $this->createQuery('j')
            ->innerJoin('j.Equipo e')
            ->where('j.id IN (SELECT ev.jugador_id FROM EventoPartido ev WHERE ev.tipo_evento = ?)', 'goal')
            ->orWhere('j.id IN (SELECT ev2.asistente_id FROM EventoPartido ev2 WHERE ev2.asistente_id IS NOT NULL AND ev2.tipo_evento = ?)', 'goal')
            ->orderBy('e.nombre ASC, j.nombre ASC')
            ->execute();
    }
}
