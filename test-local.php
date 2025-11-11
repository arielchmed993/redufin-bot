<?php
echo "🧪 INICIANDO PRUEBAS LOCALES\n";
echo "============================\n\n";

// Test de configuración básica
echo "1. 🔧 Verificando configuración...\n";

if (!file_exists('.env')) {
    echo "   ❌ Archivo .env no encontrado\n";
    echo "   📝 Creando archivo .env básico...\n";
    file_put_contents('.env', "BOT_TOKEN=test_token\nBOT_USERNAME=test_bot\nADMIN_CHAT_ID=123456789\nRENDER_URL=http://127.0.0.1");
    echo "   ✅ Archivo .env creado\n";
} else {
    echo "   ✅ Archivo .env encontrado\n";
}

// Cargar configuración
require_once 'config.php';

echo "   📊 Configuración cargada:\n";
echo "   - DB_PATH: " . DB_PATH . "\n";
echo "   - GNEWS_API_KEY: " . (defined('GNEWS_API_KEY') ? 'Configurada' : 'No configurada') . "\n";
echo "   - BOT_TOKEN: " . (BOT_TOKEN ? 'Configurado' : 'No configurado') . "\n\n";

// Test de base de datos
echo "2. 💾 Probando base de datos...\n";
try {
    initDatabase();
    $pdo = new PDO('sqlite:' . DB_PATH);
    
    // Verificar tablas
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✅ Base de datos inicializada\n";
    echo "   📋 Tablas: " . implode(', ', $tables) . "\n";
    
    // Contar usuarios
    $userCount = $pdo->query("SELECT COUNT(*) FROM telegram_users")->fetchColumn();
    echo "   👥 Usuarios registrados: $userCount\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Error en base de datos: " . $e->getMessage() . "\n\n";
}

// Test de GNews API
echo "3. 📡 Probando conexión con GNews...\n";
require_once 'news-service.php';

$news = getFinancialNews(2);
if (!empty($news)) {
    echo "   ✅ Conexión exitosa\n";
    echo "   📰 Noticias obtenidas: " . count($news) . "\n";
    foreach ($news as $index => $article) {
        echo "   " . ($index + 1) . ". " . $article['title'] . "\n";
        echo "      Fuente: " . $article['source'] . "\n";
    }
} else {
    echo "   ⚠️  No se pudieron obtener noticias de GNews (usando fallback)\n";
    echo "   ℹ️  Esto es normal en entornos locales con problemas de SSL\n";
}

echo "\n4. 📊 Probando estadísticas...\n";
$stats = getNewsStats();
echo "   📨 Noticias enviadas hoy: " . $stats['today_count'] . "\n";

echo "\n🎉 PRUEBAS COMPLETADAS\n";
echo "=====================\n";
echo "📍 Base de datos: " . DB_PATH . "\n";
echo "📝 Logs: " . dirname(DB_PATH) . "/bot.log\n";
echo "🚀 Para probar el bot: http://127.0.0.1/tu-carpeta/index.php\n";
?>