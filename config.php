<?php
// Configuración para Railway.app
$isLocal = (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === '127.0.0.1' || $_SERVER['SERVER_NAME'] === 'localhost')) || php_sapi_name() === 'cli';

// En Railway, usar variables de entorno
$env = [
    'BOT_TOKEN' => $_ENV['BOT_TOKEN'] ?? getenv('BOT_TOKEN'),
    'BOT_USERNAME' => $_ENV['BOT_USERNAME'] ?? getenv('BOT_USERNAME'),
    'ADMIN_CHAT_ID' => $_ENV['ADMIN_CHAT_ID'] ?? getenv('ADMIN_CHAT_ID'),
    'RAILWAY_URL' => $_ENV['RAILWAY_STATIC_URL'] ?? getenv('RAILWAY_STATIC_URL')
];

// Configuración del Bot de Telegram
define('BOT_TOKEN', $env['BOT_TOKEN'] ?? '8336253561:AAE1MoMaNHa2wRgGpKZK7HqajX049eSpyfs');
define('BOT_USERNAME', $env['BOT_USERNAME'] ?? 'FinanceNewsGNewsBot');

// Configuración de APIs de Noticias - GNews
define('GNEWS_API_KEY', '4fc181dbf5625dc4d3a26e3fcdb42bd2');
define('GNEWS_BASE_URL', 'https://gnews.io/api/v4');

// Configuración de la base de datos
if ($isLocal) {
    define('DB_PATH', __DIR__ . '/bot_database.sqlite');
} else {
    // En Railway, usar path persistente
    define('DB_PATH', __DIR__ . '/storage/bot_database.sqlite');
}

// Configuración general - Railway proporciona URL automáticamente
$railwayUrl = $env['RAILWAY_URL'] ?? ($_SERVER['RAILWAY_STATIC_URL'] ?? 'https://tu-app.railway.app');
define('WEBHOOK_URL', $railwayUrl);
define('ADMIN_CHAT_ID', $env['ADMIN_CHAT_ID'] ?? '123456789');

// Crear directorio storage si no existe
if (!is_dir(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

// Inicializar base de datos SQLite
function initDatabase() {
    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear tabla de usuarios si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id INTEGER UNIQUE,
        first_name TEXT,
        username TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Crear tabla de noticias enviadas si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS sent_news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        source TEXT,
        url TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

// Función para logging
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Log para consola en desarrollo local
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
    
    // También guardar en archivo local
    $logFile = dirname(DB_PATH) . '/bot.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Log para producción
    error_log($logMessage);
}
?>