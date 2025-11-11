<?php
require_once 'config.php';
require_once 'news-service.php';

// Header para respuesta web
header('Content-Type: text/plain; charset=utf-8');
echo "🚀 EJECUTANDO CRON JOB DESDE WEB\n";
echo "================================\n\n";

// Inicializar base de datos
initDatabase();

try {
    // Obtener usuarios activos
    $pdo = new PDO('sqlite:' . DB_PATH);
    $stmt = $pdo->query("SELECT chat_id, first_name, username FROM telegram_users WHERE is_active = 1");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $userCount = count($users);
    echo "👥 Usuarios activos encontrados: $userCount\n\n";
    
    if (empty($users)) {
        echo "❌ No hay usuarios activos. Agregando usuario de prueba...\n";
        
        // Agregar usuario de prueba automáticamente
        $stmt = $pdo->prepare("INSERT INTO telegram_users (chat_id, first_name, username, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([123456789, 'Usuario Web', 'webuser']);
        
        echo "✅ Usuario de prueba agregado (Chat ID: 123456789)\n\n";
        
        // Volver a obtener usuarios
        $stmt = $pdo->query("SELECT chat_id, first_name, username FROM telegram_users WHERE is_active = 1");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userCount = count($users);
    }
    
    // Mostrar usuarios
    echo "📋 Lista de usuarios:\n";
    foreach ($users as $user) {
        echo "   👤 {$user['first_name']} (@{$user['username']}) - Chat ID: {$user['chat_id']}\n";
    }
    echo "\n";
    
    // Obtener noticias
    echo "📡 Obteniendo noticias financieras...\n";
    $news = getFinancialNews(3);
    
    if (empty($news)) {
        echo "❌ No se pudieron obtener noticias\n";
        exit;
    }
    
    echo "✅ " . count($news) . " noticias obtenidas:\n";
    foreach ($news as $index => $article) {
        echo "   " . ($index + 1) . ". {$article['title']}\n";
        echo "      Fuente: {$article['source']}\n";
    }
    echo "\n";
    
    $sentCount = 0;
    $errorCount = 0;
    
    // Para cada usuario, preparar mensaje
    foreach ($users as $user) {
        $chatId = $user['chat_id'];
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
            echo "📤 Preparando mensaje para: {$user['first_name']} (Chat ID: $chatId)\n";
            
            // En modo web, solo simulamos el envío
            echo "   📝 Mensaje simulado (primeras 100 caracteres):\n";
            echo "   " . substr($message, 0, 100) . "...\n";
            
            $sentCount++;
        } else {
            echo "ℹ️  No hay noticias nuevas para: {$user['first_name']}\n";
        }
        
        echo "\n";
    }
    
    // Estadísticas finales
    echo "📊 RESUMEN FINAL:\n";
    echo "=================\n";
    echo "✅ Mensajes preparados: $sentCount\n";
    echo "❌ Errores: $errorCount\n";
    echo "👥 Total usuarios: $userCount\n";
    echo "📰 Noticias procesadas: " . count($news) . "\n";
    echo "🕒 Hora: " . date('H:i:s') . "\n\n";
    
    echo "🎉 CRON JOB COMPLETADO EXITOSAMENTE!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>