<?php
/**
 * Games Arena - Configuration & Global Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'games_arena');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'Games Arena');
define('APP_VERSION', '1.0.0');
define('APP_CURRENCY', '₹');
define('APP_CURRENCY_CODE', 'INR');

// Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);
// Normalise base path
$basePath = rtrim(str_replace('\\', '/', $scriptDir), '/');
// If inside /customer, /admin, /includes etc., climb to root
$basePath = preg_replace('#/(customer|admin|includes|config|database|assets|uploads).*$#i', '', $basePath);
define('BASE_URL', rtrim($protocol . $host . $basePath, '/'));

// Language Dictionary (English, Tamil, Hindi)
$LANGUAGES = [
    'English' => [
        'home' => 'Home',
        'search' => 'Search',
        'categories' => 'Categories',
        'goty' => 'Game of the Year',
        'favorites' => 'Favorites',
        'cart' => 'Cart',
        'orders' => 'My Orders',
        'purchased' => 'Purchased Games',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        'add_to_cart' => 'Add to Cart',
        'in_library' => 'In Library',
        'in_cart' => 'In Cart',
        'buy_now' => 'Buy Now',
        'view_details' => 'View Details',
        'popular_games' => 'Popular Games',
        'recent_games' => 'Recently Added',
        'recommended' => 'Recommended For You',
        'explore_categories' => 'Browse Categories',
        'welcome_back' => 'Welcome back',
        'ratings_reviews' => 'Ratings & Reviews',
        'system_req' => 'System Requirements',
        'minimum' => 'Minimum',
        'recommended_req' => 'Recommended',
        'checkout' => 'Proceed to Checkout',
        'total' => 'Total',
        'empty_cart' => 'Your cart is empty',
        'admin_dashboard' => 'Admin Dashboard',
        'all_games' => 'All Games',
        'users' => 'Users',
        'reviews' => 'Reviews',
        'revenue' => 'Total Revenue',
    ],
    'Tamil' => [
        'home' => 'முகப்பு',
        'search' => 'தேடல்',
        'categories' => 'வகைகள்',
        'goty' => 'ஆண்டின் சிறந்த கேம்',
        'favorites' => 'விருப்பங்கள்',
        'cart' => 'கூடை',
        'orders' => 'எனது ஆர்டர்கள்',
        'purchased' => 'வாங்கிய கேம்கள்',
        'profile' => 'சுயவிவரம்',
        'settings' => 'அமைப்புகள்',
        'logout' => 'வெளியேறு',
        'login' => 'உள்நுழைய',
        'register' => 'பதிவு செய்',
        'add_to_cart' => 'கூடையில் சேர்க்க',
        'in_library' => 'நூலகத்தில் உள்ளது',
        'in_cart' => 'கூடையில் உள்ளது',
        'buy_now' => 'இப்போதே வாங்கு',
        'view_details' => 'விவரங்களை பார்க்க',
        'popular_games' => 'பிரபலமான கேம்கள்',
        'recent_games' => 'சமீபத்தில் சேர்க்கப்பட்டவை',
        'recommended' => 'பரிந்துரைக்கப்பட்டவை',
        'explore_categories' => 'வகைகளை காண்க',
        'welcome_back' => 'மீண்டும் வருக',
        'ratings_reviews' => 'மதிப்பீடுகள் & விமர்சனங்கள்',
        'system_req' => 'கணினி தேவைகள்',
        'minimum' => 'குறைந்தபட்ச தேவை',
        'recommended_req' => 'பரிந்துரைக்கப்படும் தேவை',
        'checkout' => 'பணம் செலுத்த தொடரவும்',
        'total' => 'மொத்தம்',
        'empty_cart' => 'உங்கள் கூடை காலியாக உள்ளது',
        'admin_dashboard' => 'நிர்வாக பலகை',
        'all_games' => 'அனைத்து கேம்கள்',
        'users' => 'பயனர்கள்',
        'reviews' => 'விமர்சனங்கள்',
        'revenue' => 'மொத்த வருவாய்',
    ],
    'Hindi' => [
        'home' => 'होम',
        'search' => 'खोजें',
        'categories' => 'श्रेणियाँ',
        'goty' => 'गेम ऑफ द इयर',
        'favorites' => 'पसंदीदा',
        'cart' => 'कार्ट',
        'orders' => 'मेरे ऑर्डर्स',
        'purchased' => 'खरीदे गए गेम्स',
        'profile' => 'प्रोफ़ाइल',
        'settings' => 'सेटिंग्स',
        'logout' => 'लॉग आउट',
        'login' => 'लॉग इन',
        'register' => 'रजिस्टर करें',
        'add_to_cart' => 'कार्ट में जोड़ें',
        'in_library' => 'लाइब्रेरी में है',
        'in_cart' => 'कार्ट में है',
        'buy_now' => 'अभी खरीदें',
        'view_details' => 'विवरण देखें',
        'popular_games' => 'लोकप्रिय गेम्स',
        'recent_games' => 'हाल ही में जोड़े गए',
        'recommended' => 'आपके लिए अनुशंसित',
        'explore_categories' => 'श्रेणियां ब्राउज़ करें',
        'welcome_back' => 'वापसी पर स्वागत है',
        'ratings_reviews' => 'रेटिंग और समीक्षाएं',
        'system_req' => 'सिस्टम आवश्यकताएं',
        'minimum' => 'न्यूनतम आवश्यकता',
        'recommended_req' => 'अनुशंसित आवश्यकता',
        'checkout' => 'चेकआउट करें',
        'total' => 'कुल',
        'empty_cart' => 'आपकी कार्ट खाली है',
        'admin_dashboard' => 'एडमिन डैशबोर्ड',
        'all_games' => 'सभी गेम्स',
        'users' => 'उपयोगकर्ता',
        'reviews' => 'समीक्षाएं',
        'revenue' => 'कुल राजस्व',
    ]
];

/**
 * Get translated text for a key
 */
function __($key, $default = '') {
    global $LANGUAGES;
    $lang = $_SESSION['user_language'] ?? 'English';
    if (!isset($LANGUAGES[$lang])) {
        $lang = 'English';
    }
    return $LANGUAGES[$lang][$key] ?? ($LANGUAGES['English'][$key] ?? ($default ?: $key));
}
