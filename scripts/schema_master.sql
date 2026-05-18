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
    (1, 'La Liga', 'es'),
    (2, 'Premier League', 'en'),
    (3, 'Serie A', 'it'),
    (4, 'Ligue 1', 'fr'),
    (5, 'Bundesliga', 'de'),
    (6, 'Liga Profesional Argentina', 'ar');

INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (1, '15/16', 'whatif_es1516', 1, 1),
    (7, '16/17', 'whatif_es1617', 0, 1),
    (8, '17/18', 'whatif_es1718', 0, 1),
    (9, '18/19', 'whatif_es1819', 0, 1),
    (10, '19/20', 'whatif_es1920', 0, 1),
    (11, '20/21', 'whatif_es2021', 0, 1),
    (12, '21/22', 'whatif_es2122', 0, 1),
    (13, '22/23', 'whatif_es2223', 0, 1),
    (14, '23/24', 'whatif_es2324', 0, 1),
    (15, '24/25', 'whatif_es2425', 0, 1),
    (2, '15/16', 'whatif_en1516', 0, 2),
    (3, '15/16', 'whatif_it1516', 0, 3),
    (4, '15/16', 'whatif_fr1516', 0, 4),
    (5, '15/16', 'whatif_de1516', 0, 5),
    (6, '2015', 'whatif_ar2015', 0, 6);
