<?php
require_once 'config.php';
require_once 'news-service.php';

echo "🧪 Probando Cron Job Localmente...\n";
echo "===================================\n";

// Inicializar base de datos
initDatabase();

try {
    // Test 1: Conexión a GNews API
    echo "1. 🔍 Probando conexión a GNews API... ";
    $news = getFinancialNews(2);
    
    if (!empty($news)) {
        echo "✅ OK - " . count($news) . " noticias obtenidas\n";
        foreach ($news as $index => $article) {
            echo "   📰 " . ($index + 1) . ". " . substr($article['title'], 0, 50) . "...\n";
        }
    } else {
        echo "❌ FALLÓ - No se pudieron obtener noticias\n";
    }
    
    // Test 2: Base de datos
    echo "2. 💾 Probando base de datos... ";
    $pdo = new PDO('sqlite:' . DB_PATH);
    $stmt = $pdo->query("SELECT COUNT(*) FROM telegram_users");
    $userCount = $stmt->fetchColumn();
    echo "✅ OK - $userCount usuarios registrados\n";
    
    // Test 3: Envío de mensajes (simulado)
    echo "3. 📤 Probando envío de mensajes... ";
    if (defined('BOT_TOKEN') && BOT_TOKEN) {
        echo "✅ OK - Token configurado\n";
    } else {
        echo "⚠️  ADVERTENCIA - Token no configurado\n";
    }
    
    // Test 4: Estadísticas
    echo "4. 📊 Probando estadísticas... ";
    $stats = getNewsStats();
    echo "✅ OK - {$stats['today_count']} noticias enviadas hoy\n";
    
    echo "\n🎉 ¡Todas las pruebas completadas!\n";
    echo "📍 Base de datos: " . DB_PATH . "\n";
    echo "📝 Logs: " . dirname(DB_PATH) . "/bot.log\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>