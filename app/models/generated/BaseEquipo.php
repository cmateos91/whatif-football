<?php

abstract class BaseEquipo extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('equipos');

        $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
        $this->hasColumn('nombre', 'string', 100, ['notnull' => true]);
        $this->hasColumn('nombre_corto', 'string', 20, ['notnull' => true]);
        $this->hasColumn('escudo_url', 'string', 255);
        $this->hasColumn('estadio', 'string', 100);
    }

    public function setUp()
    {
        $this->hasMany('Jugador as Jugadores', [
            'local' => 'id',
            'foreign' => 'equipo_id',
        ]);
    }
}