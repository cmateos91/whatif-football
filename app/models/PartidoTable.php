<?php

class PartidoTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Partido');
    }

    public function findTodos()
    {
        return $this->createQuery('p')
            ->innerJoin('p.EquipoLocal el')
            ->innerJoin('p.EquipoVisitante ev')
            ->orderBy('p.jornada ASC')
            ->execute();
    }
}
