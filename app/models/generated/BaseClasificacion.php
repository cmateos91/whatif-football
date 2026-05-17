<?php

abstract class BaseClasificacion extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('clasificacion');

        $this->hasColumn('id',               'integer', 4, ['primary' => true, 'autoincrement' => true]);
        $this->hasColumn('equipo_id',        'integer', 4, ['notnull' => true]);
        $this->hasColumn('posicion',         'integer', 4, ['notnull' => true]);
        $this->hasColumn('jugados',          'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('ganados',          'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('empatados',        'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('perdidos',         'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('goles_favor',      'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('goles_contra',     'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('diferencia_goles', 'integer', 4, ['notnull' => true, 'default' => 0]);
        $this->hasColumn('puntos',           'integer', 4, ['notnull' => true, 'default' => 0]);
    }

    public function setUp()
    {
        $this->hasOne('Equipo', [
            'local'   => 'equipo_id',
            'foreign' => 'id',
        ]);
    }
}
