<?php

abstract class BasePartido extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('partidos');

        $this->hasColumn('id',                  'integer', 4,  ['primary' => true, 'autoincrement' => true]);
        $this->hasColumn('equipo_local_id',      'integer', 4,  ['notnull' => true]);
        $this->hasColumn('equipo_visitante_id',  'integer', 4,  ['notnull' => true]);
        $this->hasColumn('goles_local',          'integer', 4,  ['notnull' => true, 'default' => 0]);
        $this->hasColumn('goles_visitante',      'integer', 4,  ['notnull' => true, 'default' => 0]);
        $this->hasColumn('fecha',                'date',    null, ['notnull' => true]);
        $this->hasColumn('jornada',              'integer', 4,  ['notnull' => true]);
    }

    public function setUp()
    {
        $this->hasOne('Equipo as EquipoLocal', [
            'local'   => 'equipo_local_id',
            'foreign' => 'id',
        ]);

        $this->hasOne('Equipo as EquipoVisitante', [
            'local'   => 'equipo_visitante_id',
            'foreign' => 'id',
        ]);

        $this->hasMany('EventoPartido as Eventos', [
            'local'   => 'id',
            'foreign' => 'partido_id',
        ]);

        $this->hasMany('Alineacion as Alineaciones', [
            'local'   => 'id',
            'foreign' => 'partido_id',
        ]);
    }
}
