<?php
// Incluir configuración al inicio del archivo
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Finance News Bot - GNews</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-blue-600 mb-4 text-center">
                🤖 Telegram Finance News Bot
            </h1>
            
            <div class="space-y-6">
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h2 class="text-xl font-semibold text-green-800 mb-2">✅ Conectado a GNews API</h2>
                    <p class="text-green-700">Fuente de noticias: GNews.io - Noticias en español en tiempo real</p>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold text-blue-800 mb-2">🚀 Sistema Funcionando</h2>
                    <div class="space-y-2 text-blue-700">
                        <p><strong>Estado:</strong> 🟢 En línea</p>
                        <p><strong>API:</strong> GNews.io</p>
                        <p><strong>Idioma:</strong> Español</p>
                        <p><strong>Modo:</strong> <?php echo (php_sapi_name() === 'cli') ? 'Consola' : 'Web'; ?></p>
                    </div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold text-purple-800 mb-2">📋 Comandos Disponibles</h2>
                    <div class="space-y-2 text-purple-700">
                        <div class="flex justify-between">
                            <span>/noticias</span>
                            <span class="bg-purple-200 px-2 rounded">Noticias financieras</span>
                        </div>
                        <div class="flex justify-between">
                            <span>/mercado</span>
                            <span class="bg-purple-200 px-2 rounded">Resumen del mercado</span>
                        </div>
                        <div class="flex justify-between">
                            <span>/cripto</span>
                            <span class="bg-purple-200 px-2 rounded">Criptomonedas</span>
                        </div>
                        <div class="flex justify-between">
                            <span>/estadisticas</span>
                            <span class="bg-purple-200 px-2 rounded">Estadísticas</span>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de pruebas -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">🔧 Herramientas de Prueba</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="test-web.php" 
                           class="bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition">
                           🧪 Test Sistema
                        </a>
                        <a href="cron-web.php" 
                           class="bg-green-600 text-white text-center py-3 rounded-lg hover:bg-green-700 transition">
                           📰 Cron Job Web
                        </a>
                        <a href="simple-test.php" 
                           class="bg-purple-600 text-white text-center py-3 rounded-lg hover:bg-purple-700 transition">
                           💾 Test BD
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <a href="telegram-bot.php?set_webhook=1" 
                       class="bg-purple-600 text-white text-center py-3 rounded-lg hover:bg-purple-700 transition">
                       🔗 Configurar Webhook
                    </a>
                    <a href="cron-web.php" 
                       class="bg-green-600 text-white text-center py-3 rounded-lg hover:bg-green-700 transition">
                       📰 Probar Noticias
                    </a>
                </div>
                
                <div class="text-center">
                    <?php if (defined('BOT_USERNAME') && BOT_USERNAME): ?>
                        <a href="https://t.me/<?php echo BOT_USERNAME; ?>" 
                           target="_blank"
                           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition inline-block">
                           🚀 Usar el Bot en Telegram
                        </a>
                    <?php else: ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                            <p>⚠️ Bot de Telegram no configurado</p>
                            <p class="text-sm">Configura BOT_USERNAME en el archivo .env</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h2 class="text-xl font-semibold text-yellow-800 mb-2">📊 Estado del Sistema</h2>
                    <div class="text-yellow-700 space-y-2 text-sm">
                        <?php
                        try {
                            initDatabase();
                            $pdo = new PDO('sqlite:' . DB_PATH);
                            
                            // Usuarios
                            $userCount = $pdo->query("SELECT COUNT(*) FROM telegram_users")->fetchColumn();
                            echo "<p>• 👥 Usuarios registrados: $userCount</p>";
                            
                            // Noticias hoy
                            $newsToday = $pdo->query("SELECT COUNT(*) FROM sent_news WHERE date(sent_at) = date('now')")->fetchColumn();
                            echo "<p>• 📰 Noticias enviadas hoy: $newsToday</p>";
                            
                            // Base de datos
                            echo "<p>• 💾 Base de datos: " . basename(DB_PATH) . "</p>";
                            
                        } catch (Exception $e) {
                            echo "<p>• ❌ Error accediendo a base de datos</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>