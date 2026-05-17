<?php

require_once(__DIR__ . '/enviroment.php');

session_start();

//Configuración de Doctrine
Doctrine_Manager::getInstance()->setAttribute(
    Doctrine_Core::ATTR_MODEL_LOADING,
    Doctrine_Core::MODEL_LOADING_AGGRESSIVE
);

//Conexión a la BDMaster
Doctrine_Manager::connection(
    'mysql://' . DBUSER . ':' . DBPASSWORD . '@' . DBHOST . '/' . DBDATABASE_MASTER,
    'master'
);

//Resolver temporada activa
$conn = Doctrine_Manager::getInstance()->getConnection('master');
$stmt = $conn->execute(
    'SELECT id, nombre, db_nombre FROM temporadas WHERE ' .
        (isset($_SESSION['temporada_id']) ? 'id = ?' : 'activa = 1') .
        ' LIMIT 1',
    isset($_SESSION['temporada_id']) ? [$_SESSION['temporada_id']] : []
);
$temporada = $stmt->fetch(PDO::FETCH_OBJ);

if (!$temporada) {
    die('No hay ninguna temporada configurada');
}

$_SESSION['temporada_id'] = $temporada->id;
$_SESSION['temporada_nombre'] = $temporada->nombre;

//Conexión a la BD de la temporada
Doctrine_Manager::connection(
    'mysql://' . DBUSER . ':' . DBPASSWORD . '@' . DBHOST . '/' . $temporada->db_nombre,
    'temporada'
);

Doctrine_Core::loadModels(__DIR__ . '/app/models');
