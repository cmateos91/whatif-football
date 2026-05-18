# WhatIF Football — Resumen del proyecto

## Qué es

Simulador de fútbol que responde a la pregunta: **¿cómo habría quedado la clasificación si un jugador concreto no hubiera marcado goles y/o dado asistencias?**

El usuario elige una liga, una temporada, un jugador y el modo (solo goles / solo asistencias / ambos). El motor recalcula todos los resultados afectados y muestra la clasificación real vs. la hipotética, junto con el listado de partidos que habrían cambiado y los puntos aportados por el jugador.

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | PHP 7.4 |
| ORM | Doctrine 1.x (incluido en `/lib/vendor/doctrine/`) |
| Templates | Smarty 3 (incluido en `/lib/vendor/smarty/`) |
| Frontend | Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 (CDN) + jQuery (local en `/public/vendor/`) |
| Base de datos | MariaDB 10.6 (en producción) / MySQL 8 (en local) |
| Servidor web | Apache con mod_rewrite |
| Despliegue | Dokploy + Docker en VPS Hetzner ARM64 (`178.104.136.131`) |
| Datos | StatsBomb Open Data (JSON en `/tmp/open-data/`) |

No hay Composer. Las dependencias (Doctrine, Smarty) están incluidas directamente en el repo.

---

## Estructura de archivos

```
/
├── index.php                  # Punto de entrada — llama a init.php
├── init.php                   # Carga vendor, bootstrap y dispatcher
├── bootstrap.php              # Conexiones Doctrine (master + temporada activa)
├── enviroment.php             # Credenciales DB (gitignored, generado por Docker)
├── environment.default.php    # Plantilla de credenciales
├── .htaccess                  # Redirige todo a index.php
├── Dockerfile                 # PHP 7.4 + Apache, ARM64 compatible
├── docker-entrypoint.sh       # Genera enviroment.php desde env vars
│
├── app/
│   ├── controllers/
│   │   ├── ApplicationController.php   # Base con método render() y Smarty
│   │   ├── MainController.php          # index, temporadasPorLiga (AJAX), cambiarTemporada
│   │   └── ResultadosController.php    # calcular() — llama al WhatIfEngine
│   ├── models/
│   │   ├── generated/                  # Clases Base* generadas por Doctrine (NO editar)
│   │   ├── Jugador.php / JugadorTable.php
│   │   ├── Equipo.php / EquipoTable.php
│   │   ├── Partido.php / PartidoTable.php
│   │   ├── EventoPartido.php / EventoPartidoTable.php
│   │   ├── Alineacion.php / AlineacionTable.php
│   │   └── Clasificacion.php / ClasificacionTable.php
│   └── views/
│       ├── application.tpl             # Layout HTML base con navbar
│       └── main/index.tpl             # Formulario principal (liga, jugador, modo)
│
├── lib/
│   ├── WhatIfEngine.php               # Motor principal de simulación
│   ├── dispatcher/Dispatcher.php      # Router: /controlador/accion → ControllerClass->action()
│   └── vendor/doctrine/ + smarty/
│
├── public/
│   ├── css/app.css                    # Solo estilos no cubiertos por Bootstrap
│   ├── js/app.js                      # Toda la lógica frontend (autocomplete, AJAX, render)
│   └── vendor/jquery.min.js
│
└── scripts/
    ├── schema_master.sql              # Schema + datos de whatif_master (ligas, temporadas)
    ├── schema.sql                     # Schema de las BDs por liga (equipos, jugadores, etc.)
    └── import_statsbomb.php           # Importa datos StatsBomb JSON a MySQL
```

---

## Arquitectura de bases de datos

### `whatif_master` (metadatos)
```sql
ligas       (id, nombre, codigo)          -- ES, EN, IT, FR, DE
temporadas  (id, nombre, db_nombre, activa, liga_id)
```

### BDs por liga: `whatif_es1516`, `whatif_en1516`, `whatif_it1516`, `whatif_fr1516`, `whatif_de1516`
```sql
equipos         (id, nombre, nombre_corto, escudo_url, estadio)
jugadores       (id, nombre, equipo_id, posicion, nacionalidad, dorsal)
partidos        (id, equipo_local_id, equipo_visitante_id, goles_local, goles_visitante, fecha, jornada)
eventos_partido (id, partido_id, jugador_id, asistente_id, equipo_id, tipo_evento, minuto)
alineaciones    (id, partido_id, jugador_id, equipo_id)
clasificacion   (id, equipo_id, posicion, jugados, ganados, empatados, perdidos, gf, gc, dg, puntos)
```

`asistente_id` en `eventos_partido` referencia al jugador que dio el pase de gol (key_pass_id de StatsBomb).

---

## Routing

El dispatcher convierte la URL en `ControladorController->accion()`:
- `/` → `MainController->index()`
- `/main/temporadasPorLiga?liga_id=X` → JSON con temporadas (AJAX)
- `/main/cambiarTemporada` (POST) → guarda `$_SESSION['temporada_id']`, redirect a `/`
- `/resultados/calcular` (POST) → JSON con el resultado del motor

---

## Motor de simulación (`lib/WhatIfEngine.php`)

`calcularSinJugador(int $jugadorId, string $modo): array`

**Modos:** `'goles'`, `'asistencias'`, `'ambos'`

**Algoritmo:**
1. Obtiene el jugador y los partidos en que estuvo en la alineación
2. Obtiene sus goles propios (`jugador_id`) y los goles que asistió (`asistente_id`) en TODOS los partidos de la temporada (no solo en los que jugó — StatsBomb a veces no incluye al asistente en la alineación formal)
3. Fusiona según el modo
4. Recorre todos los partidos de la temporada, resta los goles correspondientes y recalcula el marcador
5. Si el resultado cambia (gana/pierde/empata diferente), registra el partido como afectado incluyendo `puntos_orig` y `puntos_nuevo` para el equipo del jugador
6. Construye clasificación nueva ordenada por puntos y diferencia de goles
7. Calcula `cambio_posicion` comparando con la clasificación real almacenada en BD

**Retorna:**
```php
[
  'jugador'           => string,
  'equipo'            => string,
  'total_goles'       => int,
  'total_asistencias' => int,
  'partidos_jugados'  => int,
  'original'          => [ ['posicion','equipo','puntos','gf','gc'], ... ],
  'nueva'             => [ ['posicion','equipo','puntos','gf','gc','cambio_posicion'], ... ],
  'partidos_afectados'=> [ ['jornada','local','visitante','resultado_orig','resultado_nuevo',
                            'goles_quitados','motivo','puntos_orig','puntos_nuevo'], ... ],
]
```

`motivo` puede ser `'goles'`, `'asistencias'` o `'ambos'`.

---

## Frontend (`public/js/app.js`)

Todo en jQuery dentro de `$(document).ready`.

**Funcionalidades:**
- **Selector de liga:** al cambiar, hace AJAX a `/main/temporadasPorLiga` y repopula el select de temporada. Al cambiar la temporada, el form hace POST automático.
- **Autocomplete de jugador:** filtra el array `jugadores` (inyectado por Smarty en el HTML) mientras el usuario escribe. Mínimo 2 caracteres, máximo 10 resultados.
- **Formulario de simulación:** POST AJAX a `/resultados/calcular`, llama a `renderResultado(data)`.
- **`renderResultado(data)`:** genera HTML con Bootstrap cards — stats del jugador, dos tablas de clasificación lado a lado, tabla de partidos afectados con columnas de puntos aportados y acumulado por jornada.
- **`renderTabla(filas, esNueva)`:** tabla Bootstrap con clases `.subio` / `.bajo` en filas (verde claro / rosa claro) para equipos que suben/bajan.

---

## Estilos (`public/css/app.css`)

Solo lo que Bootstrap no cubre:
- `.autocomplete-wrapper` / `#autocomplete-list` / `.autocomplete-item` — dropdown custom
- `.subio td` → `background: #d1e7dd` (verde claro) para filas de equipos que suben
- `.bajo td` → `background: #f8d7da` (rosa claro) para filas de equipos que bajan
- `.jugador-stats` — flex wrapper (legacy, puede eliminarse)
- `.error` — texto rojo

**Importante:** las reglas aplican sobre `td` (no `tr`) porque Bootstrap 5 pinta el fondo directamente en las celdas con variables CSS.

---

## Despliegue (producción)

**VPS:** Hetzner ARM64, Ubuntu 24.04, IP `178.104.136.131`  
**Panel:** Dokploy en `http://178.104.136.131:3000`  
**Dominio:** `whatif-football.duckdns.org` (DuckDNS, HTTPS con Let's Encrypt vía Traefik)

**Contenedores Docker:**
- `whatiffootball-app-*` — PHP 7.4 + Apache (imagen construida desde Dockerfile del repo)
- `whatiffootball-whatiffootballdb-*` — MariaDB 10.6

**Variables de entorno de la app:**
```
DBHOST=whatiffootball-whatiffootballdb-eiorip
DBUSER=whatif
DBPASSWORD=<password>
DBDATABASE_MASTER=whatif_master
```

**El `docker-entrypoint.sh`** genera `enviroment.php` al arrancar el contenedor desde estas variables.

**Permisos MySQL:** el usuario `whatif` tiene `GRANT ALL` sobre `whatif_master` y las 5 BDs de ligas.

---

## Importación de datos

Los datos vienen de **StatsBomb Open Data** (repositorio público de GitHub con JSON de partidos, alineaciones y eventos).

```bash
# En local, desde /tmp/open-data/
php scripts/import_statsbomb.php es   # La Liga 15/16
php scripts/import_statsbomb.php en   # Premier League 15/16
php scripts/import_statsbomb.php it   # Serie A 15/16
php scripts/import_statsbomb.php fr   # Ligue 1 15/16
php scripts/import_statsbomb.php de   # Bundesliga 15/16
```

Solo la temporada 15/16 de las 5 ligas grandes está importada (es la que StatsBomb tiene completa en su open data).

---

## Cosas a tener en cuenta

- **`enviroment.php`** (con errata en el nombre, sin la segunda `n`) es el archivo de credenciales. Está gitignored. En local se crea a mano copiando `environment.default.php`.
- **Doctrine 1** necesita cargar `generated/` antes que `models/` para respetar dependencias entre clases Base y sus hijas.
- **MariaDB 10.6 en ARM64** no soporta la collation `utf8mb4_0900_ai_ci` de MySQL 8. Si se vuelve a hacer un mysqldump desde local, hay que hacer `sed 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g'` antes de importar.
- **Bootstrap table rows:** para colorear filas en tablas Bootstrap 5 hay que apuntar a `td`, no a `tr`, porque Bootstrap usa variables CSS en las celdas.
- Los warnings de Doctrine 1 (`continue targeting switch`) están silenciados en el `php.ini` del Dockerfile (`E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING`).

---

## Repo

`https://github.com/cmateos91/whatif-football` — rama `master`
