<?php

abstract class BaseAlineacion extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('alineaciones');

        $this->hasColumn('id',         'integer', 4, ['primary' => true, 'autoincrement' => true]);
        $this->hasColumn('partido_id', 'integer', 4, ['notnull' => true]);
        $this->hasColumn('jugador_id', 'integer', 4, ['notnull' => true]);
        $this->hasColumn('equipo_id',  'integer', 4, ['notnull' => true]);
    }

    public function setUp()
    {
        $this->hasOne('Partido', [
            'local'   => 'partido_id',
            'foreign' => 'id',
        ]);

        $this->hasOne('Jugador', [
            'local'   => 'jugador_id',
            'foreign' => 'id',
        ]);

        $this->hasOne('Equipo', [
            'local'   => 'equipo_id',
            'foreign' => 'id',
        ]);
    }
}
