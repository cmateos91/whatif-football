<?php

class AlineacionTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Alineacion');
    }

    public function findPartidosPorJugador(int $jugadorId)
    {
        return $this->createQuery('a')
            ->select('a.partido_id')
            ->where('a.jugador_id = ?', $jugadorId)
            ->execute();
    }
}
