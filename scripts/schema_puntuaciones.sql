USE whatif_master;

CREATE TABLE IF NOT EXISTS puntuaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    aciertos INT NOT NULL DEFAULT 0,
    tiempo_segundos INT NOT NULL DEFAULT 0,
    temporada_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (temporada_id) REFERENCES temporadas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
