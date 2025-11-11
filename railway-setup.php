<?php
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "🚀 CONFIGURACIÓN RAILWAY - TELEGRAM FINANCE BOT\n";
echo "===============================================\n\n";

try {
    // 1. Verificar entorno
    echo "1. 🌍 Verificando entorno Railway...\n";
    echo "   - URL: " . WEBHOOK_URL . "\n";
    echo "   - Entorno: " . (php_sapi_name() === 'cli' ? 'CLI' : 'Web') . "\n";
    echo "   - PHP: " . PHP_VERSION . "\n\n";
    
    // 2. Inicializar base de datos
    echo "2. 💾 Inicializando base de datos...\n";
    initDatabase();
    $pdo = new PDO('sqlite:' . DB_PATH);
    echo "   ✅ Base de datos: " . DB_PATH . "\n\n";
    
    // 3. Verificar configuración
    echo "3. ⚙️ Verificando configuración...\n";
    echo "   - BOT_TOKEN: " . (BOT_TOKEN ? '✅ Configurado' : '❌ No configurado') . "\n";
    echo "   - BOT_USERNAME: " . (BOT_USERNAME ? '✅ ' . BOT_USERNAME : '❌ No configurado') . "\n";
    echo "   - GNEWS_API_KEY: " . (GNEWS_API_KEY ? '✅ Configurado' : '❌ No configurado') . "\n";
    echo "   - WEBHOOK_URL: " . WEBHOOK_URL . "\n\n";
    
    // 4. Configurar webhook de Telegram
    echo "4. 🔗 Configurando webhook de Telegram...\n";
    $webhookUrl = WEBHOOK_URL . '/telegram-bot.php';
    $setWebhookUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);
    
    $context = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        'http' => ['timeout' => 10]
    ]);
    
    $response = @file_get_contents($setWebhookUrl, false, $context);
    
    if ($response !== FALSE) {
        $result = json_decode($response, true);
        if ($result['ok']) {
            echo "   ✅ Webhook configurado exitosamente\n";
            echo "   📍 URL: " . $webhookUrl . "\n";
        } else {
            echo "   ❌ Error: " . ($result['description'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ⚠️  No se pudo conectar a Telegram API (puede ser normal al inicio)\n";
    }
    echo "\n";
    
    // 5. Probar servicio
    echo "5. 📡 Probando servicio de noticias...\n";
    require_once 'news-service.php';
    $news = getFinancialNews(2);
    if (!empty($news)) {
        echo "   ✅ Servicio funcionando - " . count($news) . " noticias obtenidas\n";
    } else {
        echo "   ⚠️  Servicio usando modo fallback\n";
    }
    echo "\n";
    
    // 6. Información final
    echo "🎯 INFORMACIÓN DEL BOT\n";
    echo "=====================\n";
    echo "🤖 Nombre: @FinanceNewsGNewsBot\n";
    echo "🌐 URL: " . WEBHOOK_URL . "\n";
    echo "💾 BD: " . DB_PATH . "\n";
    echo "📰 Fuente: GNews API (Español)\n\n";
    
    echo "📋 COMANDOS DISPONIBLES:\n";
    echo "• /start - Iniciar bot\n";
    echo "• /noticias - Noticias financieras\n"; 
    echo "• /mercado - Resumen mercado\n";
    echo "• /cripto - Criptomonedas\n";
    echo "• /help - Ayuda\n\n";
    
    echo "🚀 ¡Bot listo para usar en Railway!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>