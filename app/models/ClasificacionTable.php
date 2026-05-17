<?php

class ClasificacionTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Clasificacion');
    }

    public function findOrdenada()
    {
        return $this->createQuery('c')
            ->innerJoin('c.Equipo e')
            ->orderBy('c.posicion ASC')
            ->execute();
    }
}
