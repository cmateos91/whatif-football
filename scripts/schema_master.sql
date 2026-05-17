CREATE DATABASE IF NOT EXISTS whatif_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE whatif_master;

CREATE TABLE IF NOT EXISTS ligas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS temporadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    db_nombre VARCHAR(50) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 0,
    liga_id INT NOT NULL,
    FOREIGN KEY (liga_id) REFERENCES ligas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ligas (id, nombre, codigo) VALUES
    (1, 'ES', 'es'),
    (2, 'EN', 'en'),
    (3, 'IT', 'it'),
    (4, 'FR', 'fr'),
    (5, 'DE', 'de');

INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (1, '15/16', 'whatif_es1516', 1, 1),
    (2, '15/16', 'whatif_en1516', 0, 2),
    (3, '15/16', 'whatif_it1516', 0, 3),
    (4, '15/16', 'whatif_fr1516', 0, 4),
    (5, '15/16', 'whatif_de1516', 0, 5);
