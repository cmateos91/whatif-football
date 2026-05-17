CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nombre_corto VARCHAR(20) NOT NULL,
    escudo_url VARCHAR(255),
    estadio VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    equipo_id INT NOT NULL,
    posicion VARCHAR(50),
    nacionalidad VARCHAR(50),
    dorsal INT,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    goles_local INT NOT NULL DEFAULT 0,
    goles_visitante INT NOT NULL DEFAULT 0,
    fecha DATE NOT NULL,
    jornada INT NOT NULL,
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id),
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eventos_partido (
      id INT AUTO_INCREMENT PRIMARY KEY,
      partido_id INT NOT NULL,
      jugador_id INT,
      asistente_id INT,
      equipo_id INT NOT NULL,
      tipo_evento VARCHAR(20) NOT NULL COMMENT 'gol, penalty, gol en propia',
      minuto INT NOT NULL,
      FOREIGN KEY (partido_id) REFERENCES partidos(id),
      FOREIGN KEY (jugador_id) REFERENCES jugadores(id),
      FOREIGN KEY (asistente_id) REFERENCES jugadores(id),
      FOREIGN KEY (equipo_id) REFERENCES equipos(id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alineaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    equipo_id INT NOT NULL,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clasificacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    posicion INT NOT NULL,
    jugados INT NOT NULL DEFAULT 0,
    ganados INT NOT NULL DEFAULT 0,
    empatados INT NOT NULL DEFAULT 0,
    perdidos INT NOT NULL DEFAULT 0,
    goles_favor INT NOT NULL DEFAULT 0,
    goles_contra INT NOT NULL DEFAULT 0,
    diferencia_goles INT NOT NULL DEFAULT 0,
    puntos INT NOT NULL DEFAULT 0,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
