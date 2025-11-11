<?php
require_once 'config.php';

// Header para Railway
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "📰 EJECUTANDO CRON JOB - RAILWAY\n";
echo "===============================\n\n";

try {
    initDatabase();
    $pdo = new PDO('sqlite:' . DB_PATH);
    
    // Obtener usuarios activos
    $users = $pdo->query("SELECT chat_id, first_name FROM telegram_users WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "👥 Usuarios activos: " . count($users) . "\n\n";
    
    if (empty($users)) {
        echo "ℹ️  No hay usuarios activos\n";
        exit(0);
    }
    
    // Obtener noticias
    require_once 'news-service.php';
    $news = getFinancialNews(3);
    
    if (empty($news)) {
        echo "❌ No se pudieron obtener noticias\n";
        exit(0);
    }
    
    echo "📰 Noticias obtenidas: " . count($news) . "\n";
    
    $sentCount = 0;
    
    foreach ($users as $user) {
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
            // En Railway, enviar mensajes reales
            if (sendTelegramMessage($user['chat_id'], $message)) {
                $sentCount++;
                echo "✅ Enviado a: {$user['first_name']} ({$user['chat_id']})\n";
            }
            sleep(1);
        }
    }
    
    echo "\n📊 RESUMEN: $sentCount mensajes enviados\n";
    echo "🕒 " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

function sendTelegramMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
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
        return false;
    }
}
?>