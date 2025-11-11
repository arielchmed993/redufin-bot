<?php
// Encabezado para desarrollo
if (php_sapi_name() === 'cli') {
    echo "🚀 Iniciando Cron Job...\n";
    echo "=======================\n";
}

require_once 'config.php';
require_once 'news-service.php';

// Log inicial
logMessage("🔄 Cron job iniciado");

try {
    // Inicializar base de datos
    initDatabase();
    
    // Obtener usuarios activos
    $pdo = new PDO('sqlite:' . DB_PATH);
    $stmt = $pdo->query("SELECT chat_id FROM telegram_users WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $userCount = count($users);
    logMessage("📊 Usuarios activos encontrados: $userCount");
    
    if (php_sapi_name() === 'cli') {
        echo "👥 Usuarios activos: $userCount\n";
    }
    
    if (empty($users)) {
        $message = "❌ No hay usuarios activos para enviar noticias";
        logMessage($message);
        if (php_sapi_name() === 'cli') {
            echo "$message\n";
        }
        exit(0);
    }
    
    // Obtener noticias
    logMessage("📡 Obteniendo noticias de GNews...");
    if (php_sapi_name() === 'cli') {
        echo "📡 Obteniendo noticias... ";
    }
    
    $news = getFinancialNews(3);
    
    if (empty($news)) {
        $message = "❌ No se pudieron obtener noticias de GNews";
        logMessage($message);
        if (php_sapi_name() === 'cli') {
            echo "FALLÓ\n";
            echo "$message\n";
        }
        exit(0);
    }
    
    logMessage("📰 Noticias obtenidas: " . count($news));
    if (php_sapi_name() === 'cli') {
        echo "✅ " . count($news) . " noticias obtenidas\n";
    }
    
    $sentCount = 0;
    $errorCount = 0;
    
    // Para desarrollo local, simular envío
    $isLocal = $_SERVER['SERVER_NAME'] === '127.0.0.1' || $_SERVER['SERVER_NAME'] === 'localhost' || php_sapi_name() === 'cli';
    
    foreach ($users as $chatId) {
        $message = "📰 *Noticias Financieras del Día*\n\n";
        $hasContent = false;
        
        foreach ($news as $article) {
            if (!isNewsAlreadySent($article['title'])) {
                $message .= "📈 *{$article['title']}*\n";
                $message .= "{$article['description']}\n";
                $message .= "📰 Fuente: {$article['source']}\n";
                $message .= "🔗 [Leer más]({$article['url']})\n\n";
                
                markNewsAsSent($article['title'], $article['source'], $article['url']);
                $hasContent = true;
            }
        }
        
        if ($hasContent) {
            if ($isLocal) {
                // En desarrollo local, solo mostrar en consola
                logMessage("📤 [SIMULADO] Enviando a $chatId");
                if (php_sapi_name() === 'cli') {
                    echo "📤 Mensaje simulado para $chatId\n";
                }
                $sentCount++;
            } else {
                // En producción, enviar realmente
                if (sendTelegramMessage($chatId, $message)) {
                    $sentCount++;
                    logMessage("✅ Noticias enviadas a: $chatId");
                } else {
                    $errorCount++;
                    logMessage("❌ Error enviando a: $chatId");
                }
            }
        }
        
        // Pausa para evitar rate limiting (solo en producción)
        if (!$isLocal) {
            sleep(1);
        }
    }
    
    $logMessage = "✅ Cron job completado: $sentCount enviados, $errorCount errores";
    logMessage($logMessage);
    
    if (php_sapi_name() === 'cli') {
        echo "📊 Resultado: $sentCount enviados, $errorCount errores\n";
    }
    
    // Notificar al admin (solo en producción)
    if (!$isLocal && defined('ADMIN_CHAT_ID') && ADMIN_CHAT_ID) {
        $adminMessage = "📊 *Reporte Diario de Noticias*\n\n";
        $adminMessage .= "✅ Enviados: $sentCount usuarios\n";
        $adminMessage .= "❌ Errores: $errorCount\n";
        $adminMessage .= "👥 Total usuarios: $userCount\n";
        $adminMessage .= "🕒 Hora: " . date('H:i:s');
        
        sendTelegramMessage(ADMIN_CHAT_ID, $adminMessage);
    }
    
    if (php_sapi_name() === 'cli') {
        echo "🎉 Cron job finalizado exitosamente!\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    $errorMsg = "❌ Error en cron job: " . $e->getMessage();
    logMessage($errorMsg);
    
    if (php_sapi_name() === 'cli') {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "📍 En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // Notificar error al admin (solo en producción)
    if (!$isLocal && defined('ADMIN_CHAT_ID') && ADMIN_CHAT_ID) {
        sendTelegramMessage(ADMIN_CHAT_ID, $errorMsg);
    }
    
    exit(1);
}

function sendTelegramMessage($chatId, $text, $parseMode = 'Markdown') {
    // En desarrollo local, simular envío
    $isLocal = $_SERVER['SERVER_NAME'] === '127.0.0.1' || $_SERVER['SERVER_NAME'] === 'localhost' || php_sapi_name() === 'cli';
    
    if ($isLocal) {
        logMessage("📤 [SIMULADO] Mensaje para $chatId: " . substr($text, 0, 100) . "...");
        return true;
    }
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => false
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'timeout' => 10,
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    
    try {
        $result = file_get_contents($url, false, $context);
        return $result !== false;
    } catch (Exception $e) {
        logMessage("Error sending to $chatId: " . $e->getMessage());
        return false;
    }
}
?>