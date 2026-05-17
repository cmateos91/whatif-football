#!/bin/bash
set -e

cat > /var/www/html/enviroment.php <<EOF
<?php
define('DBHOST',            '${DBHOST:-localhost}');
define('DBUSER',            '${DBUSER:-root}');
define('DBPASSWORD',        '${DBPASSWORD:-}');
define('DBDATABASE_MASTER', '${DBDATABASE_MASTER:-whatif_master}');
EOF

exec "$@"
