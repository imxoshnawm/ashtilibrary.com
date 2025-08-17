<?php
class GeminiAPI {
    private $api_key;
    private $base_url;
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->base_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }
    
    public function generateContent($message, $books_context = '') {
        // Build the system prompt with Kurdish book store context
        $system_prompt = "تۆ یارمەتیدەری کتێبخانەی ئاشتی لە هەولێری کوردستان. ئەرکت ئەوەیە کە خەڵک یارمەتی بدەیت بۆ دۆزینەوەی کتێب و پێشنیاری کتێبی گونجاو. 

زانیاری کتێبخانەی ئاشتی:
- ناو: کتێبخانەی ئاشتی
- شوێن: هەولێر، کوردستان
- ناونیشانی تەواو: هەولێر - داونتاون، نهۆمی دووەم دوکانی F78 سەرەوەی ساردەمەنی جیلاتۆ
- کاتژمێرەکانی کارکردن: هەموو ڕۆژێک ٩:٠٠ بەیانی - ٨:٠٠ ئێوارە
- ژمارەی تەلەفۆن: +964 750 386 6000
- جۆرە کتێبەکان: ڕۆمان، ڕۆمانی ڕۆمانسی، ڕۆمانی ترسناک، کتێبی گەشەپێدان، شیعر، مێژوو، ئایینی، فەلسەفە، زانست

بەستەرە کۆمەڵایەتیەکان:
- Facebook: https://www.facebook.com/ktebxany.ashti
- Instagram: https://www.instagram.com/ktebxanay.ashti/
- Telegram 1: https://t.me/ktebxanai1ashti
- Telegram 2: https://t.me/ashtibookstore
- WhatsApp: https://api.whatsapp.com/send/?phone=9647503866000
- Google Maps: https://www.google.com/maps/place/Ashti+book+store

ڕێنماییەکان:
1. هەمیشە بە زمانی کوردی وەڵام بدەرەوە
2. کاتێک کەسێک داوای زانیاری کتێبخانە دەکات، زانیاری تەواو پێشکەش بکە: ناونیشان، کاتژمێرەکان، پەیوەندی
3. کاتێک کەسێک داوای کتێب دەکات، زانیاری تەواو پێشکەش بکە: ناونیشان، نووسەر، وەرگێڕ (ئەگەر هەبێت)، نرخ، و کورتەیەک لە ناوەڕۆک
4. ئەگەر کەسێک جۆرێکی دیاریکراو لە کتێب دەوێت، چەند پێشنیارێک بدە
5. نرخەکان بە دینار پیشان بدە
6. میهربان و یارمەتیدەر بە
7. ئەگەر کتێبەکە نییە، کتێبی هاوشێوەی پێشنیار بکە
8. کاتێک کەسێک داوای ناونیشان یان پەیوەندی دەکات، هەموو زانیاریەکان پێبدە
9. ئەگەر کەسێک بپرسێت چۆن بگات بە کتێبخانە، بەستەری Google Maps پێبدە

" . $books_context;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $system_prompt . "\n\nپرسیاری بەکارهێنەر: " . $message
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $this->api_key
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'هەڵەی پەیوەندی: ' . $curl_error
            ];
        }

        if ($http_code !== 200) {
            return [
                'success' => false,
                'error' => 'هەڵەی سەرڤەر: ' . $http_code
            ];
        }

        $decoded_response = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'هەڵەی وەڵامی AI'
            ];
        }

        if (isset($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'response' => $decoded_response['candidates'][0]['content']['parts'][0]['text']
            ];
        }

        return [
            'success' => false,
            'error' => 'هەڵەی لە وەڵامی AI دا'
        ];
    }
}

class BookRecommendationSystem {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    public function getRelevantBooks($user_message) {
        $keywords = [
            'ڕۆمانسی' => [3],
            'ترسناک' => [4],
            'گەشەپێدان' => [5],
            'شیعر' => [6],
            'مێژوو' => [7],
            'ئایینی' => [8],
            'فەلسەفە' => [9],
            'زانست' => [10],
            'ڕۆمان' => [2, 3, 4]
        ];
        
        $relevant_categories = [];
        $message_lower = mb_strtolower($user_message);
        
        foreach ($keywords as $keyword => $category_ids) {
            if (strpos($message_lower, $keyword) !== false) {
                $relevant_categories = array_merge($relevant_categories, $category_ids);
            }
        }
        
        if (empty($relevant_categories)) {
            // If no specific category found, get featured books
            $sql = "SELECT * FROM books WHERE is_featured = 1 OR is_bestseller = 1 ORDER BY RAND() LIMIT 5";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        } else {
            // Get books from relevant categories
            $placeholders = str_repeat('?,', count($relevant_categories) - 1) . '?';
            $sql = "SELECT * FROM books WHERE category_id IN ($placeholders) ORDER BY RAND() LIMIT 8";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($relevant_categories);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function formatBooksForAI($books) {
        if (empty($books)) {
            return "\nکتێبە بەردەستەکان: هیچ کتێبێکی تایبەت نەدۆزرایەوە.";
        }
        
        $books_info = "\nکتێبە بەردەستەکان لە کتێبخانەی ئاشتی:\n\n";
        
        foreach ($books as $book) {
            $books_info .= "📖 " . $book['book_title'] . "\n";
            $books_info .= "   نووسەر: " . $book['author'] . "\n";
            
            if (!empty($book['translator'])) {
                $books_info .= "   وەرگێڕ: " . $book['translator'] . "\n";
            }
            
            $books_info .= "   نرخ: " . number_format($book['price']) . " دینار\n";
            
            if (!empty($book['description'])) {
                $books_info .= "   کورتە: " . $book['description'] . "\n";
            }
            
            $books_info .= "\n";
        }
        
        return $books_info;
    }
}
?>