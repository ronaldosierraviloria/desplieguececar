<?php
/**
 * ========================================================
 *  TEST DE CONEXION A BASE DE DATOS
 *  Sistema de Grado
 * ========================================================
 *  Este script verifica si la aplicacion puede conectarse
 *  correctamente a la base de datos PostgreSQL.
 *
 *  Ejecutar desde el navegador:
 *  http://localhost/Sistema_Grado/test_conexion.php
 * ========================================================
 */

// Cargar variables del archivo .env
function cargarEnv($archivo) {
    if (!file_exists($archivo)) {
        return false;
    }
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $vars = [];
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }
        list($clave, $valor) = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor, " \t\n\r\0\x0B\"'");
        $vars[$clave] = $valor;
    }
    return $vars;
}

$env = cargarEnv(__DIR__ . '/.env');

if ($env === false) {
    echo "No se encontro el archivo .env\n";
    exit(1);
}

// Obtener variables de conexion
$db_host = $env['DB_HOST'] ?? '127.0.0.1';
$db_port = $env['DB_PORT'] ?? '5432';
$db_name = $env['DB_DATABASE'] ?? '';
$db_user = $env['DB_USERNAME'] ?? '';
$db_pass = $env['DB_PASSWORD'] ?? '';
$db_driver = $env['DB_CONNECTION'] ?? '';

echo "============================================================\n";
echo "       TEST DE CONEXION - SISTEMA DE GRADO\n";
echo "============================================================\n\n";

echo "CONFIGURACION DEL .ENV:\n";
echo "------------------------------------------------------------\n";
echo "  Driver:     $db_driver\n";
echo "  Host:       $db_host\n";
echo "  Port:       $db_port\n";
echo "  Database:   $db_name\n";
echo "  Username:   $db_user\n";
echo "  Password:   " . str_repeat('*', strlen($db_pass)) . "\n";
echo "============================================================\n\n";

if ($db_driver !== 'pgsql') {
    echo "[ERROR] El driver configurado es '$db_driver'. Se esperaba 'pgsql'.\n";
    exit(1);
}

echo "Intentando conectar a PostgreSQL...\n\n";

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $conexion = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Consulta de version del servidor
    $stmt = $conexion->query("SELECT version()");
    $version = $stmt->fetchColumn();

    // Verificar si existen tablas
    $stmt = $conexion->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'"
    );
    $numTablas = $stmt->fetchColumn();

    echo "============================================================\n";
    echo "  CONEXION EXITOSA\n";
    echo "============================================================\n\n";
    echo "  Estado:     CONECTADO\n";
    echo "  Servidor:   $version\n";
    echo "  Tablas:     $numTablas en schema public\n\n";
    echo "  La aplicacion se comunica correctamente con la base\n";
    echo "  de datos PostgreSQL.\n";
    echo "============================================================\n";

    $conexion = null;

} catch (PDOException $e) {
    echo "============================================================\n";
    echo "  CONEXION FALLIDA\n";
    echo "============================================================\n\n";
    echo "  Estado:     NO CONECTADO\n";
    echo "  Error:      " . $e->getMessage() . "\n\n";
    echo "  Verifique que:\n";
    echo "    1. PostgreSQL este ejecutandose en $db_host:$db_port\n";
    echo "    2. La base de datos '$db_name' exista\n";
    echo "    3. El usuario '$db_user' tenga permisos de acceso\n";
    echo "    4. La contrasena sea correcta\n";
    echo "============================================================\n";

    exit(1);
}
