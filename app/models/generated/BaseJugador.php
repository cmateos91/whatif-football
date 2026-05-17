<?php

abstract class BaseJugador extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('jugadores');

        $this->hasColumn('id',           'integer', 4,   ['primary' => true, 'autoincrement' => true]);
        $this->hasColumn('nombre',       'string',  100, ['notnull' => true]);
        $this->hasColumn('equipo_id',    'integer', 4,   ['notnull' => true]);
        $this->hasColumn('posicion',     'string',  50);
        $this->hasColumn('nacionalidad', 'string',  50);
        $this->hasColumn('dorsal',       'integer', 4);
    }

    public function setUp()
    {
        $this->hasOne('Equipo', [
            'local'   => 'equipo_id',
            'foreign' => 'id',
        ]);

        $this->hasMany('EventoPartido as Eventos', [
            'local'   => 'id',
            'foreign' => 'jugador_id',
        ]);
    }
}
