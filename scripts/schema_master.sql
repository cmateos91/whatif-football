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

-- La Liga (10 seasons)
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
    (15, '24/25', 'whatif_es2425', 0, 1);

-- Premier League (10 seasons)
INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (2, '15/16', 'whatif_en1516', 0, 2),
    (16, '16/17', 'whatif_en1617', 0, 2),
    (17, '17/18', 'whatif_en1718', 0, 2),
    (18, '18/19', 'whatif_en1819', 0, 2),
    (19, '19/20', 'whatif_en1920', 0, 2),
    (20, '20/21', 'whatif_en2021', 0, 2),
    (21, '21/22', 'whatif_en2122', 0, 2),
    (22, '22/23', 'whatif_en2223', 0, 2),
    (23, '23/24', 'whatif_en2324', 0, 2),
    (24, '24/25', 'whatif_en2425', 0, 2);

-- Serie A (10 seasons)
INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (3, '15/16', 'whatif_it1516', 0, 3),
    (25, '16/17', 'whatif_it1617', 0, 3),
    (26, '17/18', 'whatif_it1718', 0, 3),
    (27, '18/19', 'whatif_it1819', 0, 3),
    (28, '19/20', 'whatif_it1920', 0, 3),
    (29, '20/21', 'whatif_it2021', 0, 3),
    (30, '21/22', 'whatif_it2122', 0, 3),
    (31, '22/23', 'whatif_it2223', 0, 3),
    (32, '23/24', 'whatif_it2324', 0, 3),
    (33, '24/25', 'whatif_it2425', 0, 3);

-- Ligue 1 (10 seasons)
INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (4, '15/16', 'whatif_fr1516', 0, 4),
    (34, '16/17', 'whatif_fr1617', 0, 4),
    (35, '17/18', 'whatif_fr1718', 0, 4),
    (36, '18/19', 'whatif_fr1819', 0, 4),
    (37, '19/20', 'whatif_fr1920', 0, 4),
    (38, '20/21', 'whatif_fr2021', 0, 4),
    (39, '21/22', 'whatif_fr2122', 0, 4),
    (40, '22/23', 'whatif_fr2223', 0, 4),
    (41, '23/24', 'whatif_fr2324', 0, 4),
    (42, '24/25', 'whatif_fr2425', 0, 4);

-- Bundesliga (10 seasons)
INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (5, '15/16', 'whatif_de1516', 0, 5),
    (43, '16/17', 'whatif_de1617', 0, 5),
    (44, '17/18', 'whatif_de1718', 0, 5),
    (45, '18/19', 'whatif_de1819', 0, 5),
    (46, '19/20', 'whatif_de1920', 0, 5),
    (47, '20/21', 'whatif_de2021', 0, 5),
    (48, '21/22', 'whatif_de2122', 0, 5),
    (49, '22/23', 'whatif_de2223', 0, 5),
    (50, '23/24', 'whatif_de2324', 0, 5),
    (51, '24/25', 'whatif_de2425', 0, 5);

-- Liga Argentina (1 season)
INSERT IGNORE INTO temporadas (id, nombre, db_nombre, activa, liga_id) VALUES
    (6, '2015', 'whatif_ar2015', 0, 6);
