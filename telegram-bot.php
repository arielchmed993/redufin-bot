<?php
require_once 'config.php';
require_once 'news-service.php';

initDatabase();

// Obtener datos del webhook
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    exit('Invalid request');
}

logMessage("Received update: " . json_encode($update));

// Procesar el mensaje
processUpdate($update);

function processUpdate($update) {
    if (isset($update['message'])) {
        processMessage($update['message']);
    } elseif (isset($update['callback_query'])) {
        processCallbackQuery($update['callback_query']);
    }
}

function processMessage($message) {
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $from = $message['from'];
    
    // Registrar usuario
    registerUser($from, $chatId);
    
    // Procesar comandos
    switch ($text) {
        case '/start':
            sendWelcomeMessage($chatId);
            break;
        case '/noticias':
        case '/news':
            sendLatestNews($chatId);
            break;
        case '/mercado':
        case '/market':
            sendMarketUpdate($chatId);
            break;
        case '/cripto':
        case '/crypto':
            sendCryptoNews($chatId);
            break;
        case '/estadisticas':
        case '/stats':
            sendNewsStats($chatId);
            break;
        case '/help':
        case '/ayuda':
            sendHelpMessage($chatId);
            break;
        default:
            sendDefaultResponse($chatId);
    }
}

function registerUser($userData, $chatId) {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO telegram_users 
            (chat_id, first_name, username, is_active) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([
            $chatId,
            $userData['first_name'] ?? '',
            $userData['username'] ?? ''
        ]);
        
        logMessage("Usuario registrado: " . ($userData['first_name'] ?? 'N/A'));
        
    } catch (Exception $e) {
        logMessage("Error registering user: " . $e->getMessage());
    }
}

function sendWelcomeMessage($chatId) {
    $message = "🤖 *Bienvenido al Bot de Noticias Financieras GNews*\n\n";
    $message .= "📊 *Fuente de noticias:* GNews API\n";
    $message .= "🌎 *Idioma:* Español\n";
    $message .= "🕒 *Actualizaciones:* Tiempo real\n\n";
    $message .= "*📋 Comandos disponibles:*\n";
    $message .= "📈 `/noticias` - Últimas noticias financieras\n";
    $message .= "📊 `/mercado` - Resumen del mercado\n";
    $message .= "₿ `/cripto` - Noticias de criptomonedas\n";
    $message .= "📊 `/estadisticas` - Estadísticas del día\n";
    $message .= "❓ `/help` - Mostrar ayuda\n\n";
    $message .= "⚡ *Noticias en tiempo real desde GNews*";
    
    sendTelegramMessage($chatId, $message);
}

function sendLatestNews($chatId) {
    sendTelegramMessage($chatId, "📡 *Buscando noticias financieras más recientes...*");
    
    $news = getFinancialNews(5);
    
    if (empty($news)) {
        sendTelegramMessage($chatId, "❌ No se pudieron obtener noticias en este momento. Intenta más tarde.");
        return;
    }
    
    $sentCount = 0;
    foreach ($news as $article) {
        // Evitar duplicados en el mismo día
        if (!isNewsAlreadySent($article['title'])) {
            $message = "📈 *{$article['title']}*\n\n";
            $message .= "{$article['description']}\n\n";
            $message .= "📰 *Fuente:* {$article['source']}\n";
            $message .= "🕒 *Publicado:* " . formatDate($article['published_at']) . "\n";
            $message .= "🔗 [Leer artículo completo]({$article['url']})";
            
            if (sendTelegramMessage($chatId, $message)) {
                markNewsAsSent($article['title'], $article['source'], $article['url']);
                $sentCount++;
                sleep(1); // Pausa para evitar rate limiting
            }
        }
    }
    
    if ($sentCount === 0) {
        sendTelegramMessage($chatId, "✅ Ya has recibido todas las noticias recientes. Vuelve más tarde para nuevas actualizaciones.");
    } else {
        sendTelegramMessage($chatId, "✅ *{$sentCount} noticias* enviadas. ¡Mantente informado! 📊");
    }
}

function sendMarketUpdate($chatId) {
    $marketData = getMarketData();
    
    if (empty($marketData)) {
        sendTelegramMessage($chatId, "❌ No se pudieron obtener datos del mercado.");
        return;
    }
    
    $message = "📊 *Resumen del Mercado - Simulado*\n\n";
    
    foreach ($marketData as $symbol => $data) {
        $change = $data['change'];
        $changePercent = $data['change_percent'];
        $emoji = strpos($change, '+') === 0 ? '🟢' : '🔴';
        
        $message .= "{$emoji} *{$symbol}*\n";
        $message .= "💵 Precio: \${$data['price']}\n";
        $message .= "📈 Cambio: {$change} ({$changePercent})\n\n";
    }
    
    $message .= "💡 *Nota:* Los datos son simulados. GNews proporciona noticias, no datos de mercado en tiempo real.";
    
    sendTelegramMessage($chatId, $message);
}

function sendCryptoNews($chatId) {
    sendTelegramMessage($chatId, "₿ *Buscando noticias de criptomonedas...*");
    
    $news = getCryptoNewsFromGNews(3);
    
    if (empty($news)) {
        // Fallback a búsqueda general
        $news = getFinancialNews(3);
    }
    
    if (empty($news)) {
        sendTelegramMessage($chatId, "❌ No se pudieron obtener noticias de criptomonedas.");
        return;
    }
    
    $message = "₿ *Noticias de Criptomonedas*\n\n";
    
    foreach ($news as $article) {
        $message .= "🔷 *{$article['title']}*\n";
        $message .= "{$article['description']}\n";
        $message .= "📰 Fuente: {$article['source']}\n";
        $message .= "🔗 [Leer más]({$article['url']})\n\n";
    }
    
    sendTelegramMessage($chatId, $message);
}

function sendNewsStats($chatId) {
    $stats = getNewsStats();
    
    $message = "📊 *Estadísticas del Día*\n\n";
    $message .= "📰 Noticias enviadas hoy: *{$stats['today_count']}*\n\n";
    
    if (!empty($stats['top_sources'])) {
        $message .= "🏆 *Fuentes más populares:*\n";
        foreach ($stats['top_sources'] as $source) {
            $message .= "• {$source['source']}: {$source['count']} noticias\n";
        }
    }
    
    $message .= "\n🕒 Actualizado: " . date('H:i:s');
    
    sendTelegramMessage($chatId, $message);
}

function sendHelpMessage($chatId) {
    $message = "📋 *Ayuda - Comandos Disponibles*\n\n";
    $message .= "`/start` - Iniciar el bot y ver información\n";
    $message .= "`/noticias` - Últimas noticias financieras (GNews)\n";
    $message .= "`/mercado` - Resumen del mercado actual\n";
    $message .= "`/cripto` - Noticias de criptomonedas\n";
    $message .= "`/estadisticas` - Estadísticas de noticias del día\n";
    $message .= "`/help` - Mostrar este mensaje\n\n";
    $message .= "🌐 *Fuente:* GNews API - Noticias en español\n";
    $message .= "🕒 Las noticias se actualizan automáticamente cada día a las 9:00 AM.";
    
    sendTelegramMessage($chatId, $message);
}

function sendDefaultResponse($chatId) {
    $message = "❓ No entiendo ese comando. Usa `/help` para ver los comandos disponibles.\n\n";
    $message .= "Prueba con:\n";
    $message .= "• `/noticias` - Para noticias financieras\n";
    $message .= "• `/mercado` - Para ver el mercado\n";
    $message .= "• `/help` - Para ayuda completa";
    
    sendTelegramMessage($chatId, $message);
}

function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return "Hace " . $diff->i . " minutos";
        }
        return "Hace " . $diff->h . " horas";
    } elseif ($diff->days == 1) {
        return "Ayer a las " . $date->format('H:i');
    } else {
        return $date->format('d/m H:i');
    }
}

function sendTelegramMessage($chatId, $text, $parseMode = 'Markdown') {
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
        logMessage("Message sent to $chatId");
        return $result !== false;
    } catch (Exception $e) {
        logMessage("Error sending to $chatId: " . $e->getMessage());
        return false;
    }
}

// Función para configurar webhook
function setWebhook() {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook";
    $webhookUrl = WEBHOOK_URL;
    
    $data = ['url' => $webhookUrl];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    logMessage("Webhook set: $webhookUrl");
    logMessage("Webhook response: $result");
    
    return $result;
}

// Si se accede directamente, configurar webhook
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    if (isset($_GET['set_webhook'])) {
        echo setWebhook();
    } else {
        http_response_code(200);
        echo 'OK';
    }
}
?>