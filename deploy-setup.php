<?php
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "🚀 CONFIGURACIÓN DE DESPLIEGUE RENDER\n";
echo "====================================\n\n";

try {
    // 1. Inicializar base de datos
    echo "1. 💾 Inicializando base de datos...\n";
    initDatabase();
    $pdo = new PDO('sqlite:' . DB_PATH);
    echo "   ✅ Base de datos lista\n\n";
    
    // 2. Verificar configuración
    echo "2. ⚙️ Verificando configuración...\n";
    echo "   - BOT_TOKEN: " . (BOT_TOKEN ? '✅ Configurado' : '❌ No configurado') . "\n";
    echo "   - BOT_USERNAME: " . (BOT_USERNAME ? '✅ ' . BOT_USERNAME : '❌ No configurado') . "\n";
    echo "   - GNEWS_API_KEY: " . (GNEWS_API_KEY ? '✅ Configurado' : '❌ No configurado') . "\n";
    echo "   - WEBHOOK_URL: " . WEBHOOK_URL . "\n\n";
    
    // 3. Configurar webhook automáticamente
    echo "3. 🔗 Configurando webhook de Telegram...\n";
    $webhookUrl = WEBHOOK_URL . '/telegram-bot.php';
    $setWebhookUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);
    
    $response = @file_get_contents($setWebhookUrl);
    if ($response !== FALSE) {
        $result = json_decode($response, true);
        if ($result['ok']) {
            echo "   ✅ Webhook configurado: " . $webhookUrl . "\n";
        } else {
            echo "   ❌ Error configurando webhook: " . ($result['description'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ No se pudo conectar a Telegram API\n";
    }
    echo "\n";
    
    // 4. Probar servicio de noticias
    echo "4. 📡 Probando servicio de noticias...\n";
    require_once 'news-service.php';
    $news = getFinancialNews(2);
    if (!empty($news)) {
        echo "   ✅ Servicio de noticias funcionando (" . count($news) . " noticias)\n";
    } else {
        echo "   ⚠️  Servicio de noticias usando modo fallback\n";
    }
    echo "\n";
    
    // 5. Información final
    echo "🎉 CONFIGURACIÓN COMPLETADA\n";
    echo "===========================\n";
    echo "📱 Bot de Telegram: https://t.me/" . BOT_USERNAME . "\n";
    echo "🌐 URL de la app: " . WEBHOOK_URL . "\n";
    echo "💾 Base de datos: " . DB_PATH . "\n";
    echo "📰 Fuente: GNews API (Español)\n";
    echo "⏰ Cron Job: Diario a las 9:00 AM\n\n";
    
    echo "¡Tu bot está listo para usar! 🚀\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>