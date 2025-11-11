<?php
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "🔍 VERIFICACIÓN RAILWAY - TELEGRAM BOT\n";
echo "=====================================\n\n";

// Información del entorno
echo "🌍 INFORMACIÓN DEL ENTORNO:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Railway Environment: " . (getenv('RAILWAY_ENVIRONMENT') ?: 'No detectado') . "\n";
echo "Service Name: " . (getenv('RAILWAY_SERVICE_NAME') ?: 'No detectado') . "\n";
echo "Static URL: " . (getenv('RAILWAY_STATIC_URL') ?: 'No detectada') . "\n\n";

// Verificar extensiones
echo "🔧 EXTENSIONES PHP:\n";
$extensions = ['pdo', 'pdo_sqlite', 'sqlite3', 'curl', 'json'];
foreach ($extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅" : "❌") . "\n";
}
echo "\n";

// Verificar base de datos
echo "💾 BASE DE DATOS:\n";
try {
    initDatabase();
    $pdo = new PDO('sqlite:' . DB_PATH);
    
    // Verificar tablas
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas: " . implode(', ', $tables) . "\n";
    
    // Verificar usuarios
    $userCount = $pdo->query("SELECT COUNT(*) FROM telegram_users")->fetchColumn();
    echo "Usuarios: $userCount\n";
    
    echo "Estado: ✅ Funcionando\n";
} catch (Exception $e) {
    echo "Estado: ❌ Error - " . $e->getMessage() . "\n";
}
echo "\n";

// Verificar configuración
echo "⚙️ CONFIGURACIÓN:\n";
echo "BOT_TOKEN: " . (BOT_TOKEN ? "✅ Configurado" : "❌ No configurado") . "\n";
echo "BOT_USERNAME: " . BOT_USERNAME . "\n";
echo "WEBHOOK_URL: " . WEBHOOK_URL . "\n";
echo "DB_PATH: " . DB_PATH . "\n\n";

// Probar GNews
echo "📡 PRUEBA GNEWS API:\n";
require_once 'news-service.php';
$news = getFinancialNews(1);
if (!empty($news)) {
    echo "Estado: ✅ Conectado\n";
    echo "Noticia: " . $news[0]['title'] . "\n";
} else {
    echo "Estado: ⚠️ Usando fallback\n";
}

echo "\n🚀 VERIFICACIÓN COMPLETADA\n";
?>