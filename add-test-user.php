<?php
require_once 'config.php';

echo "👤 Agregando usuario de prueba...\n";
echo "================================\n";

initDatabase();

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar la estructura de la tabla
    echo "1. 🔍 Verificando estructura de la tabla...\n";
    $stmt = $pdo->query("PRAGMA table_info(telegram_users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   📋 Columnas de telegram_users:\n";
    foreach ($columns as $col) {
        echo "   - {$col['name']} ({$col['type']})\n";
    }
    
    // Agregar usuario de prueba - solo las columnas que existen
    echo "\n2. ➕ Agregando usuario de prueba...\n";
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO telegram_users (chat_id, first_name, username, is_active) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([123456789, 'Usuario Prueba', 'test_user', 1]);
    
    if ($result) {
        echo "   ✅ Usuario agregado exitosamente\n";
    } else {
        echo "   ❌ Error al agregar usuario\n";
    }
    
    // Verificar usuarios
    echo "\n3. 📊 Listando usuarios en la base de datos:\n";
    $users = $pdo->query("SELECT chat_id, first_name, username, is_active FROM telegram_users")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "   ℹ️ No hay usuarios en la base de datos\n";
    } else {
        foreach ($users as $user) {
            echo "   👤 {$user['first_name']} (@{$user['username']})";
            echo " - Chat ID: {$user['chat_id']}";
            echo " - Activo: " . ($user['is_active'] ? '✅' : '❌') . "\n";
        }
    }
    
    // Verificar noticias enviadas
    echo "\n4. 📰 Noticias en la base de datos:\n";
    $news = $pdo->query("SELECT COUNT(*) as count FROM sent_news")->fetch(PDO::FETCH_ASSOC);
    echo "   📨 Total noticias registradas: {$news['count']}\n";
    
    echo "\n🎉 Proceso completado!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 En archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Mostrar más detalles del error
    if (strpos($e->getMessage(), 'HY000') !== false) {
        echo "\n💡 Posible solución:\n";
        echo "   - Verificar que la tabla telegram_users existe\n";
        echo "   - Verificar los nombres de las columnas\n";
    }
}
?>