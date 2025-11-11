<?php
// Configuración para Railway
$isRailway = getenv('RAILWAY_ENVIRONMENT') === 'production' || (isset($_SERVER['RAILWAY_ENVIRONMENT']) && $_SERVER['RAILWAY_ENVIRONMENT'] === 'production');

if ($isRailway) {
    // En Railway - usar variables de entorno
    define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '8336253561:AAE1MoMaNHa2wRgGpKZK7HqajX049eSpyfs');
    define('BOT_USERNAME', getenv('BOT_USERNAME') ?: 'FinanceNewsGNewsBot');
    define('ADMIN_CHAT_ID', getenv('ADMIN_CHAT_ID') ?: '123456789');
    define('DB_PATH', __DIR__ . '/storage/bot_database.sqlite');
    define('WEBHOOK_URL', getenv('RAILWAY_STATIC_URL') ?: 'https://' . getenv('RAILWAY_SERVICE_NAME') . '.up.railway.app');
} else {
    // Desarrollo local
    define('BOT_TOKEN', '8336253561:AAE1MoMaNHa2wRgGpKZK7HqajX049eSpyfs');
    define('BOT_USERNAME', 'FinanceNewsGNewsBot');
    define('ADMIN_CHAT_ID', '123456789');
    define('DB_PATH', __DIR__ . '/bot_database.sqlite');
    define('WEBHOGO_URL', 'http://localhost:8000');
}

// Configuración de APIs
define('GNEWS_API_KEY', '4fc181dbf5625dc4d3a26e3fcdb42bd2');
define('GNEWS_BASE_URL', 'https://gnews.io/api/v4');

// Crear directorio storage si no existe
if (!is_dir(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

// Inicializar base de datos
function initDatabase() {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chat_id INTEGER UNIQUE,
        first_name TEXT,
        username TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS sent_news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        source TEXT,
        url TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage);
    
    // También guardar en archivo
    $logFile = dirname(DB_PATH) . '/bot.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
?>