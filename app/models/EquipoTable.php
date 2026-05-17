<?php

class EquipoTable extends Doctrine_Table
{
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Equipo');
    }

    public function findTodos()
    {
        return $this->findAll();
    }
}