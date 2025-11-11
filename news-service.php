<?php
require_once 'config.php';

function getFinancialNews($limit = 5) {
    $news = [];
    
    // Intentar GNews API primero
    $gnews = getNewsFromGNews($limit);
    if (!empty($gnews)) {
        $news = $gnews;
        logMessage("✅ Noticias obtenidas de GNews: " . count($news));
    } else {
        // Fallback a noticias predefinidas
        $news = getNewsFromFallback($limit);
        logMessage("⚠️ Usando noticias de fallback: " . count($news));
    }
    
    return array_slice($news, 0, $limit);
}

function getNewsFromGNews($limit = 5) {
    try {
        $keywords = [
            'finanzas', 'bolsa', 'mercado', 'inversiones', 'economía',
            'wall street', 'NASDAQ', 'S&P', 'bitcoin', 'criptomonedas'
        ];
        
        // Tomar 2 keywords aleatorias para variedad
        shuffle($keywords);
        $searchTerms = implode(' OR ', array_slice($keywords, 0, 2));
        
        $url = GNEWS_BASE_URL . "/search?" . http_build_query([
            'q' => $searchTerms,
            'lang' => 'es',
            'country' => 'mx',
            'max' => $limit,
            'apikey' => GNEWS_API_KEY
        ]);
        
        logMessage("🔗 Solicitando a GNews: " . $url);
        
        // Configuración de contexto para SSL
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'http' => [
                'timeout' => 10,
                'user_agent' => 'TelegramFinanceBot/1.0',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($contextOptions);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            $error = error_get_last();
            throw new Exception("Error fetching from GNews: " . ($error['message'] ?? 'Unknown error'));
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['articles']) && is_array($data['articles'])) {
            $articles = [];
            foreach ($data['articles'] as $article) {
                $articles[] = [
                    'title' => cleanText($article['title']),
                    'description' => cleanText($article['description'] ?? 'Sin descripción disponible'),
                    'source' => $article['source']['name'] ?? 'GNews',
                    'url' => $article['url'],
                    'published_at' => $article['publishedAt'] ?? date('c')
                ];
            }
            return $articles;
        } else {
            logMessage("❌ Respuesta inesperada de GNews: " . substr($response, 0, 200));
            return [];
        }
        
    } catch (Exception $e) {
        logMessage("❌ Error GNews API: " . $e->getMessage());
        return [];
    }
}

function getMarketNewsFromGNews($limit = 3) {
    try {
        $url = GNEWS_BASE_URL . "/top-headlines?" . http_build_query([
            'category' => 'business',
            'lang' => 'es',
            'country' => 'mx',
            'max' => $limit,
            'apikey' => GNEWS_API_KEY
        ]);
        
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'http' => [
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== FALSE) {
            $data = json_decode($response, true);
            if (isset($data['articles']) && is_array($data['articles'])) {
                $articles = [];
                foreach ($data['articles'] as $article) {
                    $articles[] = [
                        'title' => cleanText($article['title']),
                        'description' => cleanText($article['description'] ?? 'Noticia de negocios'),
                        'source' => $article['source']['name'] ?? 'GNews',
                        'url' => $article['url'],
                        'published_at' => $article['publishedAt'] ?? date('c')
                    ];
                }
                return $articles;
            }
        }
    } catch (Exception $e) {
        logMessage("Error GNews Market News: " . $e->getMessage());
    }
    
    return [];
}

function getCryptoNewsFromGNews($limit = 3) {
    try {
        $url = GNEWS_BASE_URL . "/search?" . http_build_query([
            'q' => 'bitcoin OR criptomonedas OR blockchain OR crypto',
            'lang' => 'es',
            'max' => $limit,
            'apikey' => GNEWS_API_KEY
        ]);
        
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ],
            'http' => [
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== FALSE) {
            $data = json_decode($response, true);
            if (isset($data['articles']) && is_array($data['articles'])) {
                $articles = [];
                foreach ($data['articles'] as $article) {
                    $articles[] = [
                        'title' => cleanText($article['title']),
                        'description' => cleanText($article['description'] ?? 'Noticia de criptomonedas'),
                        'source' => $article['source']['name'] ?? 'GNews',
                        'url' => $article['url'],
                        'published_at' => $article['publishedAt'] ?? date('c')
                    ];
                }
                return $articles;
            }
        }
    } catch (Exception $e) {
        logMessage("Error GNews Crypto News: " . $e->getMessage());
    }
    
    return [];
}

function getMarketData() {
    // Simular datos de mercado
    $symbols = ['AAPL', 'MSFT', 'TSLA', 'GOOGL', 'AMZN', 'META'];
    $marketData = [];
    
    foreach ($symbols as $symbol) {
        $price = rand(100, 500) + rand(0, 99) / 100;
        $change = (rand(0, 1) ? 1 : -1) * (rand(1, 20) + rand(0, 99) / 100);
        $changePercent = ($change / $price) * 100;
        
        $marketData[$symbol] = [
            'price' => number_format($price, 2),
            'change' => ($change > 0 ? '+' : '') . number_format($change, 2),
            'change_percent' => ($changePercent > 0 ? '+' : '') . number_format($changePercent, 2) . '%'
        ];
    }
    
    return $marketData;
}

function getNewsFromFallback($limit) {
    // Noticias predefinidas en español como fallback
    $fallbackNews = [
        [
            'title' => 'Mercados financieros muestran tendencia positiva',
            'description' => 'Los principales índices bursátiles experimentan crecimiento en la sesión actual.',
            'source' => 'Sistema',
            'url' => 'https://www.bloomberg.com',
            'published_at' => date('c')
        ],
        [
            'title' => 'Análisis: Perspectivas de inversión para el próximo trimestre',
            'description' => 'Expertos analizan las oportunidades de inversión en diferentes sectores.',
            'source' => 'Sistema',
            'url' => 'https://www.reuters.com',
            'published_at' => date('c')
        ],
        [
            'title' => 'Actualización: Divisas y materias primas',
            'description' => 'Seguimiento a los movimientos en los mercados de divisas y commodities.',
            'source' => 'Sistema',
            'url' => 'https://www.investing.com',
            'published_at' => date('c')
        ],
        [
            'title' => 'Tecnología financiera: Innovaciones en el sector bancario',
            'description' => 'Las fintech continúan transformando los servicios financieros tradicionales.',
            'source' => 'Sistema',
            'url' => 'https://www.coindesk.com',
            'published_at' => date('c')
        ]
    ];
    
    shuffle($fallbackNews);
    return array_slice($fallbackNews, 0, $limit);
}

function cleanText($text) {
    if (empty($text)) {
        return 'Descripción no disponible';
    }
    
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim($text);
    
    if (strlen($text) > 300) {
        $text = substr($text, 0, 297) . '...';
    }
    
    return $text;
}

function markNewsAsSent($title, $source, $url) {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $stmt = $pdo->prepare("INSERT INTO sent_news (title, source, url) VALUES (?, ?, ?)");
        $stmt->execute([$title, $source, $url]);
        return true;
    } catch (Exception $e) {
        logMessage("Error marking news as sent: " . $e->getMessage());
        return false;
    }
}

function isNewsAlreadySent($title) {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sent_news WHERE title = ? AND date(sent_at) = date('now')");
        $stmt->execute([$title]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        logMessage("Error checking if news already sent: " . $e->getMessage());
        return false;
    }
}

function getNewsStats() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        
        // Total noticias enviadas hoy
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sent_news WHERE date(sent_at) = date('now')");
        $todayCount = $stmt->fetchColumn();
        
        // Fuentes más populares
        $stmt = $pdo->prepare("SELECT source, COUNT(*) as count FROM sent_news WHERE date(sent_at) = date('now') GROUP BY source ORDER BY count DESC LIMIT 5");
        $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'today_count' => $todayCount,
            'top_sources' => $sources
        ];
    } catch (Exception $e) {
        logMessage("Error getting news stats: " . $e->getMessage());
        return ['today_count' => 0, 'top_sources' => []];
    }
}
?>