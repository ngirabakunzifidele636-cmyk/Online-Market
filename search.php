<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$search_query = '';
$search_results = [];
$error = '';

// Function to highlight search terms - MOVED OUTSIDE THE LOOP
function highlight_search($text, $search_query) {
    if (empty($search_query) || empty(trim($search_query))) return htmlspecialchars($text);
    // Escape special regex characters
    $pattern = preg_quote($search_query, '/');
    return preg_replace(
        "/($pattern)/i", 
        '<span class="search-highlight">$1</span>', 
        htmlspecialchars($text)
    );
}

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    
    // COMBINE ALL PRODUCTS FROM ALL YOUR PAGES
    $all_products = [];
    
    // Products from products.php (12 products)
    $products_from_products = [
        ['id' => 1, 'name' => 'MacBook Pro 16"', 'price' => 2399.99, 'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'description' => 'M2 Pro chip, 32GB RAM, 1TB SSD', 'category' => 'laptops', 'page' => 'products.php'],
        ['id' => 2, 'name' => 'iPhone 14 Pro', 'price' => 999.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.9hc21uhoFsvmooUD4cy-hQHaEk?pid=Api&P=0&h=220', 'description' => 'Dynamic Island, 48MP Camera', 'category' => 'smartphones', 'page' => 'products.php'],
        ['id' => 3, 'name' => 'Sony WH-1000XM5', 'price' => 299.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.pq794ka6QwngkQCDQsIIswHaEk?pid=Api&P=0&h=220', 'description' => 'Industry-leading noise cancellation', 'category' => 'headphones', 'page' => 'products.php'],
        ['id' => 4, 'name' => 'Apple Watch Series 8', 'price' => 399.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.kdX4SBHMXUO0zX0lYej-dgHaEK?pid=Api&P=0&h=220', 'description' => 'Advanced health monitoring', 'category' => 'smartwatch', 'page' => 'products.php'],
        ['id' => 5, 'name' => 'iPhone 16 Pro', 'price' => 1399.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.4kwxCwKQDvW_CerEY6UZwgHaEK?pid=Api&P=0&h=220', 'description' => 'Dynamic Island, 48MP Camera', 'category' => 'smartphones', 'page' => 'products.php'],
        ['id' => 6, 'name' => 'iPhone 17', 'price' => 1799.99, 'image' => 'https://images.unsplash.com/photo-1757709608566-4b9fd41a7af5?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MjB8fGlwaG9uZSUyMDE3fGVufDB8fDB8fHww&auto=format&fit=crop&q=60&w=500', 'description' => 'Dynamic Island, 48MP Camera', 'category' => 'smartphones', 'page' => 'products.php'],
        ['id' => 7, 'name' => 'Samsung galaxy S24 ultra', 'price' => 1499.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.UpAy4ZW2u3AEzD0jAJ25hgHaE8?pid=Api&P=0&h=220', 'description' => 'Hole-Punch Cutout, 200MP', 'category' => 'smartphones', 'page' => 'products.php'],
        ['id' => 8, 'name' => 'Samsung galaxy S25 ultra', 'price' => 1599.99, 'image' => 'https://images.unsplash.com/photo-1738830251513-a7bfef4b53c6?ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8U2Ftc3VuZyUyMGdhbGF4eSUyMFMyNSUyMHVsdHJhfGVufDB8fDB8fHww&auto=format&fit=crop&q=60&w=500', 'description' => 'Hole-Punch Cutout, 200MP', 'category' => 'smartphones', 'page' => 'products.php'],
        ['id' => 9, 'name' => 'HP Intel Core i5', 'price' => 1099.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.iw708rdaREs2EP3bcqrK8AHaFj?pid=Api&P=0&h=220', 'description' => '8GB RAM, 256GB SSD, Windows 10 Home', 'category' => 'laptops', 'page' => 'products.php'],
        ['id' => 10, 'name' => 'Dell laptop', 'price' => 999.99, 'image' => 'https://www.digitaltrends.com/wp-content/uploads/2018/02/dell-xps-13-screen-lid1.jpg?fit=1500%2C1000&p=1', 'description' => '16GB RAM, 1TB SSD, Windows 11 Home', 'category' => 'laptops', 'page' => 'products.php'],
        ['id' => 11, 'name' => 'Galaxy Books 5Pro 360 ', 'price' => 1999.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.sxhTC2pfQvfyRGYAz3UtVgHaEK?pid=Api&P=0&h=220', 'description' => '16GB RAM, 1TB SSD, Windows 11 Home', 'category' => 'laptops', 'page' => 'products.php'],
        ['id' => 12, 'name' => 'lenovo think book 15 ', 'price' => 2999.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.yGRLGFYwKkNhIDRxriaStQHaHa?pid=Api&P=0&h=220', 'description' => '16GB RAM, 1TB SSD, Windows 11 Home', 'category' => 'laptops', 'page' => 'products.php'],
    ];
    
    // Products from smartphones.php (12 products)
    $products_from_smartphones = [
        ['id' => 101, 'name' => 'iPhone 16 Pro', 'price' => 1399.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.79iLKQ4BbAkA61zg9ng89QHaEK?pid=Api&P=0&h=220', 'description' => 'A17 Pro chip, 6.7" Super Retina XDR', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 102, 'name' => 'iPhone 17', 'price' => 1799.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.RyXsQQsUUE7OQwceYrkWWQHaEK?pid=Api&P=0&h=220', 'description' => 'Dynamic Island, 48MP Camera, Titanium design', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 103, 'name' => 'Samsung Galaxy S24 Ultra', 'price' => 1499.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.UpAy4ZW2u3AEzD0jAJ25hgHaE8?pid=Api&P=0&h=220', 'description' => '200MP Camera, Snapdragon 8 Gen 3', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 104, 'name' => 'Samsung Galaxy S25 Ultra', 'price' => 1599.99, 'image' => 'https://images.unsplash.com/photo-1738830251513-a7bfef4b53c6?ixlib=rb-4.1.0&auto=format&fit=crop&w=500&q=60', 'description' => 'Hole-Punch Cutout, 200MP, S Pen included', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 105, 'name' => 'Google Pixel 8 Pro', 'price' => 999.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.U7HVeS3kX0GfJonXrsfZAQHaEK?pid=Api&P=0&h=220', 'description' => 'Tensor G3 chip, 6.7" OLED, 50MP camera', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 106, 'name' => 'OnePlus 12', 'price' => 899.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.v9W8W4_2F11-B1m8KahemwHaEC?pid=Api&P=0&h=220', 'description' => 'Snapdragon 8 Gen 3, 100W fast charging', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 107, 'name' => 'Xiaomi 14 Pro', 'price' => 849.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.k9BZyL4Gh3BPvbEWDE8hNAHaEK?pid=Api&P=0&h=220', 'description' => 'Leica camera, 6.73" AMOLED, 120Hz', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 108, 'name' => 'Sony Xperia 1 V', 'price' => 1299.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.RKnr6UKk_7wmvWZJ1aEIUQHaEi?pid=Api&P=0&h=220', 'description' => '4K OLED, Zeiss optics, CinemaWide display', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 109, 'name' => 'Nothing Phone 2', 'price' => 699.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.k9z66uYxQAJrDlcHWsgccgHaEK?pid=Api&P=0&h=220', 'description' => 'Glyph Interface, Snapdragon 8+ Gen 1', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 110, 'name' => 'Asus ROG Phone 8', 'price' => 1199.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.EuEKwQSSKOtR78gLQl-KYwHaEK?pid=Api&P=0&h=220', 'description' => 'Gaming phone, 165Hz AMOLED, AirTriggers', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 111, 'name' => 'Motorola Edge 40 Pro', 'price' => 799.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.z92KRJmGv43_Ll6iuHLhrQHaEK?pid=Api&P=0&h=220', 'description' => 'Curved pOLED, 165Hz, 125W turbo charging', 'category' => 'smartphones', 'page' => 'smartphones.php'],
        ['id' => 112, 'name' => 'iPhone 15 Pro Max', 'price' => 1199.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.HdFzMBPOKKKvmqjw28b1NAHaE7?pid=Api&P=0&h=220', 'description' => 'Titanium, A16 Bionic, Action button', 'category' => 'smartphones', 'page' => 'smartphones.php'],
    ];
    
    // Products from laptops.php (12 products)
    $products_from_laptops = [
        ['id' => 301, 'name' => 'MacBook Pro 16" M3 Max', 'price' => 3499.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.RF3HOYzVvP7XjMJYnlTsXQHaEK?pid=Api&P=0&h=220', 'description' => 'M3 Max, 48GB RAM, 2TB SSD, Liquid Retina XDR', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 302, 'name' => 'Dell XPS 15', 'price' => 1999.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.hSos162cCyBM6uVNiOFmFQHaE8?pid=Api&P=0&h=220', 'description' => 'Intel Core i9, 32GB RAM, 1TB SSD, OLED 3.5K', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 303, 'name' => 'HP Spectre x360 16', 'price' => 1799.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.1rYN2y4XNQumSne-qeqPUAHaEK?pid=Api&P=0&h=220', 'description' => '2-in-1, Intel Core i7, 16GB RAM, 1TB SSD', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 304, 'name' => 'Lenovo ThinkPad X1 Carbon', 'price' => 2199.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.BGXkBB5spQ4mPRNyzM9puQHaE8?pid=Api&P=0&h=220', 'description' => 'Intel Core i7, 32GB RAM, 2TB SSD, 14" 4K', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 305, 'name' => 'Asus ROG Zephyrus G16', 'price' => 2499.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.YmBSSxjr-CvSOhj8ifrjXAHaEK?pid=Api&P=0&h=220', 'description' => 'RTX 4080, Intel Core i9, 32GB RAM, 2TB SSD', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 306, 'name' => 'Microsoft Surface Laptop Studio 2', 'price' => 2399.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.Fh0b4Ripfi-R6e8pltdWygHaFj?pid=Api&P=0&h=220', 'description' => '2-in-1, RTX 4060, 32GB RAM, 1TB SSD', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 307, 'name' => 'Razer Blade 16', 'price' => 3299.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.J3KpSRoHGrWF5XDPMWWXpwHaFj?pid=Api&P=0&h=220', 'description' => 'RTX 4090, Mini LED, Intel Core i9, 64GB RAM', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 308, 'name' => 'Acer Swift Edge 16', 'price' => 1299.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.MRg6_1Y5AylZ7x-G-A8ToAHaE8?pid=Api&P=0&h=220', 'description' => 'OLED 3.2K, Ryzen 7, 16GB RAM, 1TB SSD', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 309, 'name' => 'Samsung Galaxy Book3 Ultra', 'price' => 2799.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.WPferih-sI4IaHmVv4S5pwAAAA?pid=Api&P=0&h=220', 'description' => '3K AMOLED, RTX 4070, Intel Core i9, 32GB RAM', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 310, 'name' => 'LG Gram 17', 'price' => 1899.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.2Bv1SBzLuSxhGi5hPR4mnQHaEK?pid=Api&P=0&h=220', 'description' => 'Ultra-light 2.98lbs, Intel Core i7, 32GB RAM', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 311, 'name' => 'MSI Stealth 14 Studio', 'price' => 2199.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.I2f55huq_MJ5JvMtHZAGDgHaEK?pid=Api&P=0&h=220', 'description' => 'RTX 4070, Intel Core i9, 32GB RAM, QHD+ 240Hz', 'category' => 'laptops', 'page' => 'laptops.php'],
        ['id' => 312, 'name' => 'Framework Laptop 16', 'price' => 1899.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.KOExlyocNIDWF7TvTZlgFgHaE8?pid=Api&P=0&h=220', 'description' => 'Modular design, Ryzen 9, 32GB RAM, user-repairable', 'category' => 'laptops', 'page' => 'laptops.php'],
    ];
    
    // Products from smartwatch.php (12 products)
    $products_from_smartwatch = [
        ['id' => 201, 'name' => 'Apple Watch Series 9', 'price' => 399.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.OXa_h1Q1o7XD84geloAoWwHaEK?pid=Api&P=0&h=220', 'description' => 'Always-On Retina display, S9 chip', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 202, 'name' => 'Apple Watch Ultra 2', 'price' => 799.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.McXAmkEEEsYYAWuDhtl-yAHaEK?pid=Api&P=0&h=220', 'description' => 'Titanium case, 36-hour battery, dive computer', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 203, 'name' => 'Samsung Galaxy Watch 6', 'price' => 349.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.2aGI3VU-498GcO3eJZz3SgHaEG?pid=Api&P=0&h=220', 'description' => 'Super AMOLED, Wear OS, 40-hour battery', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 204, 'name' => 'Garmin Fenix 7 Pro', 'price' => 899.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.aKUdoYYHIQFAUFgqN3TZMAHaE0?pid=Api&P=0&h=220', 'description' => 'Solar charging, 24-day battery, advanced GPS', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 205, 'name' => 'Fitbit Sense 2', 'price' => 299.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.dU0N_rGDVDJZmHnCvuSBBAHaEK?pid=Api&P=0&h=220', 'description' => 'Stress management, EDA scan, 6+ day battery', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 206, 'name' => 'Google Pixel Watch 2', 'price' => 349.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.49SB6vgfj6kMEAkPlKDyFwHaEK?pid=Api&P=0&h=220', 'description' => 'Wear OS, Fitbit integration, 24-hour battery', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 207, 'name' => 'Amazfit GTR 4', 'price' => 199.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.szr8Mt1-gCAM_pt5J3upIwHaER?pid=Api&P=0&h=220', 'description' => '14-day battery, 150+ sports modes', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 208, 'name' => 'Withings ScanWatch 2', 'price' => 349.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.62uUDNgXZ6yIv-VyLE7GVwHaEK?pid=Api&P=0&h=220', 'description' => 'Medical-grade ECG, 30-day battery', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 209, 'name' => 'Huawei Watch GT 4', 'price' => 249.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.4cIoL-LAz0i0F6vBeH4EqgHaEA?pid=Api&P=0&h=220', 'description' => '1.43" AMOLED, 14-day battery, HarmonyOS', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 210, 'name' => 'Xiaomi Watch S3', 'price' => 179.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.T8Pob2qNeOksZR51sEViMgHaDb?pid=Api&P=0&h=220', 'description' => 'Interchangeable bezels, 15-day battery', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 211, 'name' => 'Suunto Vertical', 'price' => 749.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.GldIgQi3HozRGIIknb3QwQHaEK?pid=Api&P=0&h=220', 'description' => 'Solar power, offline maps, 60+ hour GPS', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
        ['id' => 212, 'name' => 'Fossil Gen 6', 'price' => 299.99, 'image' => 'https://tse3.mm.bing.net/th/id/OIP.-qPv9lFbHFJ9PU3F6fLMuQHaEK?pid=Api&P=0&h=220', 'description' => 'Wear OS, fast charging, always-on display', 'category' => 'smartwatch', 'page' => 'smartwatch.php'],
    ];
    
    // Products from headphones.php (12 products)
    $products_from_headphones = [
        ['id' => 401, 'name' => 'Sony WH-1000XM5', 'price' => 399.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.pXQ8_gwu1GEdd5z_M8taMAHaEK?pid=Api&P=0&h=220', 'description' => 'Industry-leading noise cancellation, 30-hour battery', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 402, 'name' => 'Apple AirPods Max', 'price' => 549.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.zFnHhF4VdaqfvpKvYn6mdAHaEm?pid=Api&P=0&h=220', 'description' => 'Active Noise Cancellation, Spatial Audio', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 403, 'name' => 'Bose QuietComfort Ultra', 'price' => 429.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP.JJt8_-0URT9LYc6UJFzcGAHaFj?pid=Api&P=0&h=220', 'description' => 'Immersive Audio, 24-hour battery life', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 404, 'name' => 'Sennheiser Momentum 4', 'price' => 349.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP._ACONSOHl05R5Hmcvp_4YwHaHa?pid=Api&P=0&h=220', 'description' => '60-hour battery, Adaptive Noise Cancellation', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 405, 'name' => 'AirPods Pro 2', 'price' => 249.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.I7bCrW_97R9jbay0sAldKwHaEK?pid=Api&P=0&h=220', 'description' => 'Active Noise Cancellation, MagSafe charging', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 406, 'name' => 'Beats Studio Pro', 'price' => 349.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.ryNkzXQ-c7COmi4Vcnnv0QHaEK?pid=Api&P=0&h=220', 'description' => 'Apple H1 chip, 40-hour battery, ANC', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 407, 'name' => 'Jabra Elite 10', 'price' => 249.99, 'image' => 'https://tse4.mm.bing.net/th/id/OIP._JRnChXZ-_8dLleMGK5f4wHaEk?pid=Api&P=0&h=220', 'description' => 'Dolby Atmos, adaptive ANC, 8-hour battery', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 408, 'name' => 'Samsung Galaxy Buds2 Pro', 'price' => 229.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.16syOSSl1PljXD1r3nOBcwHaHr?pid=Api&P=0&h=220', 'description' => '24-bit Hi-Fi, Intelligent ANC, 360 Audio', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 409, 'name' => 'Bowers & Wilkins Px8', 'price' => 699.99, 'image' => 'https://tse2.mm.bing.net/th/id/OIP.MvsRiHLYGn2-AR9InTCUjgHaEc?pid=Api&P=0&h=220', 'description' => 'Carbon cone drivers, 30-hour battery, ANC', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 410, 'name' => 'Audio-Technica ATH-M50xBT2', 'price' => 199.99, 'image' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=70', 'description' => 'Studio monitor sound, 50-hour battery', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 411, 'name' => 'Sony WF-1000XM5', 'price' => 299.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.Kb-u4ogPLzDMGS_sbaMq8QHaHa?pid=Api&P=0&h=220', 'description' => 'Industry-leading ANC earbuds, 24-hour battery', 'category' => 'headphones', 'page' => 'headphones.php'],
        ['id' => 412, 'name' => 'Bang & Olufsen Beoplay H95', 'price' => 899.99, 'image' => 'https://tse1.mm.bing.net/th/id/OIP.2vBGsyS89aDUeu0c7Gt1GAHaHH?pid=Api&P=0&h=220', 'description' => 'Luxury headphones, 50-hour battery, ANC', 'category' => 'headphones', 'page' => 'headphones.php'],
    ];
    
    // Combine all products
    $all_products = array_merge(
        $products_from_products,
        $products_from_smartphones,
        $products_from_laptops,
        $products_from_smartwatch,
        $products_from_headphones
    );
    
    // Simple search logic
    foreach ($all_products as $product) {
        if (stripos($product['name'], $search_query) !== false || 
            stripos($product['description'], $search_query) !== false ||
            stripos($product['category'], $search_query) !== false) {
            $search_results[] = $product;
        }
    }
    
    if (empty($search_results)) {
        $error = "No products found for '{$search_query}'";
    }
} else {
    $error = "Please enter a search term";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - TechStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-highlight {
            background-color: yellow;
            font-weight: bold;
            padding: 2px;
            border-radius: 3px;
        }
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .price {
            color: #28a745;
            font-weight: bold;
            font-size: 1.2em;
        }
        .btn-add-to-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-add-to-cart:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: scale(1.05);
        }
        .category-badge {
            font-size: 0.7em;
            padding: 3px 8px;
            border-radius: 12px;
        }
        .category-smartphones {
            background-color: #667eea;
            color: white;
        }
        .category-laptops {
            background-color: #f5576c;
            color: white;
        }
        .category-smartwatch {
            background-color: #5B86E5;
            color: white;
        }
        .category-headphones {
            background-color: #4facfe;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4"><i class="fas fa-search"></i> Search Results</h1>
        
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="search.php" method="GET" class="row g-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg" 
                                   name="q" value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Search for products, brands, categories" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="window.location.href='products.php'">
                            <i class="fas fa-list"></i> Browse All
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($search_query)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Found <strong><?php echo count($search_results); ?></strong> result(s) for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                <?php if (!empty($search_results)): ?>
                    <a href="#results" class="btn btn-sm btn-outline-info float-end">Jump to Results</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error && empty($search_results)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            
            <div class="text-center mt-5">
                <h4>Try searching for:</h4>
                <div class="mt-3">
                    <a href="search.php?q=iPhone" class="btn btn-outline-primary m-2">iPhone</a>
                    <a href="search.php?q=Samsung" class="btn btn-outline-primary m-2">Samsung</a>
                    <a href="search.php?q=MacBook" class="btn btn-outline-primary m-2">MacBook</a>
                    <a href="search.php?q=Watch" class="btn btn-outline-primary m-2">Watch</a>
                    <a href="search.php?q=Sony" class="btn btn-outline-primary m-2">Sony</a>
                    <a href="search.php?q=laptop" class="btn btn-outline-primary m-2">Laptop</a>
                    <a href="search.php?q=headphones" class="btn btn-outline-primary m-2">Headphones</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($search_results)): ?>
            <div id="results" class="row">
                <?php foreach ($search_results as $product): 
                    // Determine category class
                    $category_class = 'category-' . $product['category'];
                    $category_name = ucfirst($product['category']);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="<?php echo $product['image']; ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo highlight_search($product['name'], $search_query); ?></h5>
                                <span class="category-badge <?php echo $category_class; ?>">
                                    <?php echo $category_name; ?>
                                </span>
                            </div>
                            <p class="card-text flex-grow-1"><?php echo highlight_search($product['description'], $search_query); ?></p>
                            <div class="mt-auto">
                                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                                <form method="POST" action="<?php echo $product['page']; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                    <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-add-to-cart w-100">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-external-link-alt"></i> 
                                        From: <a href="<?php echo $product['page']; ?>" class="text-decoration-none"><?php echo $product['page']; ?></a>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Search Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Search Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Results by Category:</h6>
                            <ul class="list-group">
                                <?php
                                $category_counts = [];
                                foreach ($search_results as $product) {
                                    $category = $product['category'];
                                    $category_counts[$category] = isset($category_counts[$category]) ? $category_counts[$category] + 1 : 1;
                                }
                                foreach ($category_counts as $category => $count):
                                    $percentage = round(($count / count($search_results)) * 100, 1);
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo ucfirst($category); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Price Range:</h6>
                            <?php
                            $prices = array_column($search_results, 'price');
                            $min_price = min($prices);
                            $max_price = max($prices);
                            $avg_price = array_sum($prices) / count($prices);
                            ?>
                            <div class="list-group">
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Minimum Price:</span>
                                        <strong class="text-success">$<?php echo number_format($min_price, 2); ?></strong>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Maximum Price:</span>
                                        <strong class="text-success">$<?php echo number_format($max_price, 2); ?></strong>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Average Price:</span>
                                        <strong class="text-success">$<?php echo number_format($avg_price, 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Search Tips -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Search Tips</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul>
                            <li>Search by product name: <code>iPhone 16 Pro</code></li>
                            <li>Search by brand: <code>Apple</code>, <code>Samsung</code>, <code>Sony</code></li>
                            <li>Search by category: <code>smartphones</code>, <code>laptops</code>, <code>headphones</code></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul>
                            <li>Search by features: <code>noise cancellation</code>, <code>OLED</code>, <code>wireless</code></li>
                            <li>Search by price: <code>under $500</code>, <code>over $1000</code></li>
                            <li>Combine terms: <code>Apple watch</code>, <code>Samsung phone</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TechStore</h5>
                    <p>Find the perfect tech products with our powerful search.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 TechStore. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput && searchInput.value === '') {
                searchInput.focus();
            }
            
            // Add keyboard shortcut for search
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === '/') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        });
    </script>
</body>
</html>