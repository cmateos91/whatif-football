<?php

class EventoPartidoTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('EventoPartido');
    }

    public function findGolesPorJugadorEnPartidos(int $jugadorId, array $partidoIds)
    {
        return $this->createQuery('e')
            ->where('e.jugador_id = ?', $jugadorId)
            ->andWhere('e.tipo_evento = ?', 'goal')
            ->andWhereIn('e.partido_id', $partidoIds)
            ->execute();
    }
}
