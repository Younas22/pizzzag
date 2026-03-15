<?php
$jsonData = file_get_contents('menu-data.json');
$menuData = json_decode($jsonData, true);

// Build lookup by category id
$categories = [];
foreach ($menuData['categories'] as $cat) {
    $categories[$cat['id']] = $cat;
}

// Helper: render a simple item card
function renderItemCard($item, $section) {
    $image = !empty($item['image']) ? htmlspecialchars($item['image']) : htmlspecialchars($section['image']);
    $name = htmlspecialchars($item['name']);
    $desc = isset($item['description']) ? $item['description'] : '';
    // Remove old brand traces from descriptions
    $desc = str_replace(['Pizza Hub', 'Kacha Khuh', 'pizza hub', 'kacha khuh'], ['PizzaG', '', 'PizzaG', ''], $desc);
    $desc = htmlspecialchars(trim($desc));
    $whatsapp = isset($item['whatsapp']) ? htmlspecialchars($item['whatsapp'], ENT_QUOTES) : '';

    $descHtml = $desc ? '<p class="text-gray-400 text-xs mb-2">' . $desc . '</p>' : '';

    // Check if item has sizes (pizza with multiple sizes)
    if (isset($item['sizes'])) {
        $sizeTexts = [];
        foreach ($item['sizes'] as $s) {
            $sizeTexts[] = htmlspecialchars($s['size']) . ': ' . htmlspecialchars($s['price']);
        }
        $priceHtml = '<span class="text-xs font-bold text-[#FFC700] leading-tight">' . implode(' | ', $sizeTexts) . '</span>';
        $whatsappText = $whatsapp ?: $name;
    } else {
        $price = htmlspecialchars($item['price'] ?? '');
        $priceHtml = '<span class="text-xl font-bold text-[#FFC700]">' . $price . '</span>';
        $whatsappText = $whatsapp ?: $name . ' - ' . ($item['price'] ?? '');
    }

    $whatsappJs = str_replace("'", "\\'", $whatsappText);

    return '
    <div class="menu-card bg-[#111111] rounded-xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C] transition-all duration-300">
        <div class="h-48 flex items-center justify-center overflow-hidden bg-[#1a1a1a]">
            <img src="' . $image . '" alt="' . $name . '" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
        </div>
        <div class="p-4">
            <h4 class="text-base font-bold text-[#FF671C] mb-1">' . $name . '</h4>
            ' . $descHtml . '
            <div class="flex justify-between items-center mt-2">
                ' . $priceHtml . '
                <button onclick="orderWhatsapp(\'' . $whatsappJs . '\')" class="bg-[#FF671C] hover:bg-orange-600 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-1 text-sm font-semibold">
                    <i class="fab fa-whatsapp"></i> Order
                </button>
            </div>
        </div>
    </div>';
}

// Helper: render a full section (title + grid of items)
function renderSection($section, $isFirst = true) {
    $icon = htmlspecialchars($section['icon']);
    $title = htmlspecialchars($section['title']);

    $html = '';
    if ($isFirst) {
        $html .= '<h2 class="text-2xl md:text-3xl font-bold text-[#FF671C] mb-6 border-b-2 border-[#FF671C]/40 pb-2">
            <i class="' . $icon . ' text-[#FFC700] mr-2"></i> ' . $title . '
        </h2>';
    } else {
        $html .= '<h3 class="text-xl md:text-2xl font-bold text-[#FF671C] mt-10 mb-6 border-b-2 border-[#FF671C]/40 pb-2">
            <i class="' . $icon . ' text-[#FFC700] mr-2"></i> ' . $title . '
        </h3>';
    }

    // If section has flavors (regular pizza flavors)
    if (isset($section['flavors'])) {
        $sizes = $section['sizes'] ?? [];
        $sizeText = [];
        foreach ($sizes as $s) {
            $sizeText[] = '<span class="font-bold text-[#FFC700]">' . htmlspecialchars($s['size']) . '</span> <span class="text-gray-300">(' . htmlspecialchars($s['price']) . ')</span>';
        }
        if (!empty($sizeText)) {
            $html .= '<p class="text-gray-400 mb-4">Available in: ' . implode(' | ', $sizeText) . '</p>';
        }

        $html .= '<div class="menu-grid">';
        foreach ($section['flavors'] as $flavor) {
            $flavorName = is_array($flavor) ? htmlspecialchars($flavor['name']) : htmlspecialchars($flavor);
            $flavorImage = (is_array($flavor) && !empty($flavor['image'])) ? htmlspecialchars($flavor['image']) : htmlspecialchars($section['image']);
            $minPrice = !empty($sizes) ? $sizes[0]['price'] : '';
            $maxPrice = !empty($sizes) ? $sizes[count($sizes) - 1]['price'] : '';
            $priceRange = $minPrice && $maxPrice ? htmlspecialchars($minPrice) . ' - ' . htmlspecialchars(str_replace('Rs. ', '', $maxPrice)) : '';
            $whatsappJs = str_replace("'", "\\'", $flavorName . ' Pizza');

            $html .= '
            <div class="menu-card bg-[#111111] rounded-xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C] transition-all duration-300">
                <div class="h-48 flex items-center justify-center overflow-hidden bg-[#1a1a1a]">
                    <img src="' . $flavorImage . '" alt="' . $flavorName . '" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                </div>
                <div class="p-4">
                    <h4 class="text-base font-bold text-[#FF671C] mb-1">' . $flavorName . '</h4>
                    <p class="text-gray-400 text-xs mb-2">' . $flavorName . ' Pizza</p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-lg font-bold text-[#FFC700]">' . $priceRange . '</span>
                        <button onclick="orderWhatsapp(\'' . $whatsappJs . '\')" class="bg-[#FF671C] hover:bg-orange-600 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-1 text-sm font-semibold">
                            <i class="fab fa-whatsapp"></i> Order
                        </button>
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';
    }

    // Render regular items
    if (isset($section['items']) && !empty($section['items'])) {
        if (isset($section['flavors'])) {
            $html .= '<h3 class="text-xl md:text-2xl font-bold text-[#FF671C] mt-10 mb-6 border-b-2 border-[#FF671C]/40 pb-2">
                <i class="' . $icon . ' text-[#FFC700] mr-2"></i> Other Items
            </h3>';
        }
        $html .= '<div class="menu-grid">';
        foreach ($section['items'] as $item) {
            $html .= renderItemCard($item, $section);
        }
        $html .= '</div>';
    }

    return $html;
}

// Helper: render all sections for a category
function renderCategory($cat) {
    $html = '';
    foreach ($cat['sections'] as $index => $section) {
        $html .= renderSection($section, $index === 0);
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PizzaG | Best Pizza Delivery on Kacha Khuh Railway Station | Order Online | 0305-8223131</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="PizzaG - Order fresh & delicious pizza, burgers, shawarma, wraps & more. Fast delivery on Kacha Khuh Railway Station. Call now: 0305-8223131. Best deals & affordable prices! Delight in Every Bite...!">
    <meta name="keywords" content="PizzaG, pizza delivery, best pizza GT Road, pizza near me, burger delivery, shawarma, fast food delivery, online food order, pizza deals, 03058223131, 03058223131">
    <meta name="author" content="PizzaG">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="https://pizzahubkk.com/">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="restaurant">
    <meta property="og:site_name" content="PizzaG">
    <meta property="og:title" content="PizzaG | Best Pizza Delivery | Order Online">
    <meta property="og:description" content="Order fresh & delicious pizza, burgers, shawarma & more from PizzaG. Fast delivery on Kacha Khuh Railway Station. Call: 0305-8223131">
    <meta property="og:image" content="https://pizzahubkk.com/icons/pizzag-logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="https://pizzahubkk.com/">
    <meta property="og:locale" content="en_PK">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="PizzaG | Best Pizza Delivery">
    <meta name="twitter:description" content="Order fresh pizza, burgers, shawarma & more. Fast delivery on Kacha Khuh Railway Station. Call: 0305-8223131">
    <meta name="twitter:image" content="https://pizzahubkk.com/icons/pizzag-logo.png">

    <!-- Geographic Meta Tags -->
    <meta name="geo.region" content="PK-PB">
    <meta name="geo.placename" content="Kacha Khuh Railway Station, Punjab, Pakistan">
    <meta name="geo.position" content="31.5204;74.3587">
    <meta name="ICBM" content="31.5204, 74.3587">

    <!-- Contact Information -->
    <meta name="contact" content="0305-8223131, 0305-8223131">
    <meta name="reply-to" content="pizzag@gmail.com">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#FF671C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PizzaG">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="PizzaG">
    <meta name="msapplication-TileColor" content="#FF671C">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="format-detection" content="telephone=yes">

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon-precomposed" href="icons/icon-192x192.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/icon-72x72.png">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7D85607QXP"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-7D85607QXP');
    </script>

    <!-- Schema.org Structured Data - Restaurant -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "PizzaG",
        "alternateName": "PizzaG Restaurant",
        "description": "Best pizza delivery on Kacha Khuh Railway Station. Fresh & delicious pizza, burgers, shawarma, wraps and more. Fast delivery with affordable prices. Delight in Every Bite...!",
        "url": "https://pizzahubkk.com/",
        "logo": "https://pizzahubkk.com/icons/pizzag-logo.png",
        "image": ["https://pizzahubkk.com/icons/pizzag-logo.png"],
        "telephone": ["+92-305-8223131", "+92-305-8223131"],
        "priceRange": "$$",
        "servesCuisine": ["Pizza", "Fast Food", "Pakistani", "Italian"],
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Kacha Khuh Railway Station",
            "addressLocality": "Kacha Khuh",
            "addressRegion": "Punjab",
            "addressCountry": "PK"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": 31.5204,
            "longitude": 74.3587
        },
        "openingHoursSpecification": [
            {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
                "opens": "11:00",
                "closes": "01:00"
            }
        ],
        "sameAs": ["https://wa.me/923058223131"],
        "potentialAction": {
            "@type": "OrderAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order",
                "actionPlatform": [
                    "https://schema.org/DesktopWebPlatform",
                    "https://schema.org/MobileWebPlatform"
                ]
            },
            "deliveryMethod": "http://purl.org/goodrelations/v1#DeliveryModeOwnFleet"
        }
    }
    </script>

    <!-- PWA Styles -->
    <link rel="stylesheet" href="pwa-styles.css">

    <!-- Tailwind CSS -->
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-orange': '#FF671C',
                    'brand-yellow': '#FFC700',
                    'brand-black': '#0a0a0a',
                    'brand-dark': '#111111',
                    'brand-darker': '#1a1a1a',
                    'brand-offwhite': '#f5f5f5',
                },
                fontFamily: {
                    'poppins': ['Poppins', 'sans-serif'],
                }
            }
        }
    }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Poppins', sans-serif; }

        body { background-color: #0a0a0a; }

        /* Scroll behavior */
        html { scroll-behavior: smooth; }

        /* Card hover */
        .menu-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(255, 103, 28, 0.15);
        }

        /* Menu grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        @media (max-width: 640px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 0.75rem;
            }
        }

        /* Tab pills */
        .tab-pill { transition: all 0.25s ease; }
        .tab-pill.active {
            background-color: #FF671C;
            color: #ffffff;
            border-color: #FF671C;
        }
        .tab-pill:not(.active) {
            background-color: transparent;
            color: #FF671C;
            border-color: #FF671C;
        }
        .tab-pill:not(.active):hover {
            background-color: #FF671C22;
        }

        /* Tabs scrollbar */
        .tabs-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tabs-container::-webkit-scrollbar { height: 3px; }
        .tabs-container::-webkit-scrollbar-thumb { background: #FF671C; border-radius: 2px; }

        /* Hero image background */
        .hero-section {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a00 50%, #0a0a0a 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 50%, rgba(255,103,28,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Nav active indicator */
        .nav-btn.active { position: relative; }
        .nav-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background: #FF671C;
            border-radius: 2px;
        }

        /* Delivery area cards */
        .area-card {
            border: 1px solid rgba(245,245,245,0.2);
            transition: all 0.25s ease;
        }
        .area-card:hover {
            border-color: #FF671C;
            color: #FF671C;
        }

        /* Section divider */
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #FF671C, transparent);
        }

        /* Offer card CTA buttons */
        .cta-phone {
            background: linear-gradient(135deg, #FF671C, #e55a15);
        }
        .cta-phone:hover { background: linear-gradient(135deg, #e55a15, #cc4f10); }

        /* Animate hero pizza */
        @keyframes floatUp {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(2deg); }
        }
        .hero-pizza-float { animation: floatUp 4s ease-in-out infinite; }

        /* Body bottom pad for nav */
        body { padding-bottom: 70px; }

        /* Glow text effect */
        .orange-glow { text-shadow: 0 0 20px rgba(255, 103, 28, 0.5); }

        @media (max-width: 640px) {
            .tab-pill { padding: 0.35rem 0.7rem; font-size: 0.75rem; }
        }
    </style>
</head>
<body class="bg-[#0a0a0a] text-[#f5f5f5]">

    <!-- ═══════════════════════════════════════ -->
    <!-- 1. HEADER / NAVIGATION                 -->
    <!-- ═══════════════════════════════════════ -->
    <header class="sticky top-0 z-50 bg-[#0a0a0a]/90 backdrop-blur-md border-b border-[#FF671C]/20 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-3">

            <!-- Logo + Brand -->
            <a href="#" class="flex items-center space-x-3 flex-shrink-0">
                <img src="icons/main-pizzag-logo.png" alt="PizzaG Logo" class="h-16 md:h-20 w-auto object-contain">
                <div class="hidden sm:block">
                    <h1 class="text-xl md:text-2xl font-black text-[#f5f5f5] leading-none">PizzaG</h1>
                    <p class="text-[10px] text-[#FFC700] font-semibold tracking-wide">Delight in Every Bite...!</p>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center space-x-6 text-sm font-semibold">
                <a href="#" class="text-[#f5f5f5] hover:text-[#FF671C] transition">Home</a>
                <a href="#menu" class="text-[#f5f5f5] hover:text-[#FF671C] transition">Menu</a>
                <a href="#hot-deals" class="text-[#f5f5f5] hover:text-[#FF671C] transition">Deals</a>
                <a href="#about-us" class="text-[#f5f5f5] hover:text-[#FF671C] transition">About</a>
                <a href="#delivery-areas" class="text-[#f5f5f5] hover:text-[#FF671C] transition">Delivery Areas</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <a href="tel:03058223131" class="hidden md:flex items-center gap-2 bg-[#111111] border border-[#f5f5f5]/20 text-[#f5f5f5] font-semibold text-sm px-3 py-2 rounded-lg hover:border-[#FF671C] transition">
                    <i class="fas fa-phone text-[#FF671C]"></i> 0305-8223131
                </a>
                <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order" target="_blank"
                   class="flex items-center gap-2 bg-[#FF671C] hover:bg-orange-600 text-white font-bold px-4 py-2 rounded-full transition-all duration-200 shadow-lg shadow-orange-900/30">
                    <i class="fab fa-whatsapp"></i>
                    <span class="hidden sm:inline text-sm">Order Now</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════ -->
    <!-- 2. HERO SECTION                        -->
    <!-- ═══════════════════════════════════════ -->
    <section class="hero-section min-h-[80vh] flex items-start pt-10 pb-12 px-4">
        <div class="max-w-7xl mx-auto w-full">
            <div class="flex flex-col lg:flex-row items-center lg:items-start gap-10 lg:gap-16">

                <!-- Left: Text Content -->
                <div class="flex-1 text-center lg:text-left">
                    <p class="text-[#FFC700] font-semibold text-sm uppercase tracking-widest mb-3">Premium Fast-Casual · Since 2024</p>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-[#f5f5f5] leading-tight mb-4">
                        PizzaG — Your <br>
                        <span class="text-[#FF671C] orange-glow">Ultimate</span> Pizza Fix
                    </h1>
                    <p class="text-[#f5f5f5]/70 text-lg md:text-xl mb-3 leading-relaxed">
                        Premium Fast Food at Kacha Khuh Railway Station
                    </p>
                    <p class="text-[#FFC700] font-bold text-xl md:text-2xl mb-8 italic">
                        "Delight in Every Bite...!"
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                        <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order" target="_blank"
                           class="inline-flex items-center justify-center gap-2 bg-[#FF671C] hover:bg-orange-600 text-white font-bold text-lg px-8 py-4 rounded-xl transition-all duration-200 shadow-xl shadow-orange-900/40 hover:scale-105">
                            <i class="fab fa-whatsapp"></i> Order Online Now
                        </a>
                        <a href="#menu"
                           class="inline-flex items-center justify-center gap-2 border-2 border-[#FF671C] text-[#FF671C] hover:bg-[#FF671C] hover:text-white font-bold text-lg px-8 py-4 rounded-xl transition-all duration-200">
                            <i class="fas fa-utensils"></i> View Menu
                        </a>
                    </div>

                    <!-- Quick Info -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start text-sm text-[#f5f5f5]/60">
                        <span><i class="fas fa-map-marker-alt text-[#FF671C] mr-1"></i> Kacha Khuh Railway Station</span>
                        <span><i class="fas fa-phone text-[#FF671C] mr-1"></i> 0305-8223131</span>
                        <span><i class="fas fa-clock text-[#FF671C] mr-1"></i> 11:00 AM – 1:00 AM</span>
                    </div>
                </div>

                <!-- Right: Main Restaurant Image -->
                <div class="flex-shrink-0 w-full max-w-sm lg:max-w-lg xl:max-w-xl">
                    <img src="icons/main/main.png" alt="PizzaG Premium Pizza"
                         class="relative z-10 w-full h-auto rounded-2xl shadow-2xl object-contain">
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 3. ABOUT US                            -->
    <!-- ═══════════════════════════════════════ -->
    <section id="about-us" class="bg-[#111111] py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row items-center gap-12">

                <!-- Left: Text -->
                <div class="flex-1">
                    <p class="text-[#FFC700] font-semibold text-sm uppercase tracking-widest mb-3">Who We Are</p>
                    <h2 class="text-3xl md:text-4xl font-black text-[#f5f5f5] mb-6">
                        The Story Behind <span class="text-[#FF671C]">PizzaG</span>
                    </h2>
                    <p class="text-[#f5f5f5]/75 text-base md:text-lg leading-relaxed mb-5">
                        PizzaG is a bold, flavour-forward fast-casual restaurant born in 2024 at Kacha Khuh Railway Station.
                        We believe that every bite deserves to be a delight — which is why we craft every pizza,
                        burger, and shawarma with the freshest ingredients and uncompromising passion.
                    </p>
                    <p class="text-[#f5f5f5]/75 text-base md:text-lg leading-relaxed mb-8">
                        From our signature pizzas to loaded burgers, wraps, and more — PizzaG is your
                        one-stop destination for premium fast food delivered right to your door.
                    </p>

                    <div class="flex flex-wrap gap-6 mb-8">
                        <div class="text-center">
                            <div class="text-3xl font-black text-[#FF671C]">2024</div>
                            <div class="text-xs text-[#f5f5f5]/50 uppercase tracking-wide">Established</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-black text-[#FF671C]">50+</div>
                            <div class="text-xs text-[#f5f5f5]/50 uppercase tracking-wide">Menu Items</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-black text-[#FF671C]">20+</div>
                            <div class="text-xs text-[#f5f5f5]/50 uppercase tracking-wide">Delivery Areas</div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="tel:03058223131"
                           class="inline-flex items-center justify-center gap-2 bg-[#FF671C] hover:bg-orange-600 text-white font-bold px-6 py-3 rounded-xl transition-all duration-200">
                            <i class="fas fa-phone"></i> Call Us Now
                        </a>
                        <a href="mailto:pizzag@gmail.com"
                           class="inline-flex items-center justify-center gap-2 border-2 border-[#FF671C] text-[#FF671C] hover:bg-[#FF671C] hover:text-white font-bold px-6 py-3 rounded-xl transition-all duration-200">
                            <i class="fas fa-envelope"></i> Email Us
                        </a>
                    </div>
                </div>

                <!-- Right: Logo / Image -->
                <div class="flex-shrink-0 flex flex-col items-center gap-6">
                    <div class="relative">
                        
                        <img src="icons/pizzag-logo.png" alt="PizzaG Logo"
                             class="relative z-10 w-56 md:w-72 h-auto object-contain drop-shadow-2xl">
                    </div>
                    <!-- Address Card -->
                    <div class="bg-[#1a1a1a] border border-[#FF671C]/20 rounded-xl p-5 text-center max-w-xs">
                        <i class="fas fa-map-marker-alt text-[#FF671C] text-xl mb-2"></i>
                        <p class="text-[#f5f5f5]/80 text-sm leading-relaxed">
                            <span class="text-[#FF671C] font-semibold">Kacha Khuh Railway Station</span><br>
                            Kacha Khuh, Punjab
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="section-divider"></div>

    <!-- ═══════════════════════════════════════ -->
    <!-- 4. MENU SELECTION                      -->
    <!-- ═══════════════════════════════════════ -->
    <section id="menu" class="bg-[#0a0a0a] py-12 px-4">
        <div class="max-w-7xl mx-auto">

            <div class="text-center mb-8">
                <p class="text-[#FFC700] font-semibold text-sm uppercase tracking-widest mb-2">What We Serve</p>
                <h2 class="text-3xl md:text-4xl font-black text-[#f5f5f5]">Our <span class="text-[#FF671C]">Menu</span></h2>
            </div>

            <!-- Category Tabs -->
            <div class="tabs-container mb-8">
                <div class="flex flex-wrap justify-center gap-2 pb-2">
                    <button onclick="showCategory('regular', this)" class="tab-pill active px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-pizza-slice mr-1"></i> Regular Pizza
                    </button>
                    <button onclick="showCategory('special', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-star mr-1"></i> Special Pizza
                    </button>
                    <button onclick="showCategory('deals', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-tags mr-1"></i> Deals
                    </button>
                    <button onclick="showCategory('burgers', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-burger mr-1"></i> Burgers
                    </button>
                    <button onclick="showCategory('fries', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-fire mr-1"></i> Fries
                    </button>
                    <button onclick="showCategory('chicken', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-drumstick-bite mr-1"></i> Chicken
                    </button>
                    <button onclick="showCategory('platters', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Platters
                    </button>
                    <button onclick="showCategory('pasta', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Pasta
                    </button>
                    <button onclick="showCategory('sandwich', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Sandwich
                    </button>
                    <button onclick="showCategory('wraps', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Wraps
                    </button>
                    <button onclick="showCategory('paratha', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Paratha
                    </button>
                    <button onclick="showCategory('shawarma', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Shawarma
                    </button>
                    <button onclick="showCategory('extras', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        Extras
                    </button>
                    <button onclick="showCategory('beverages', this)" class="tab-pill px-4 py-2 rounded-full font-semibold border-2 whitespace-nowrap text-sm">
                        <i class="fas fa-glass-water mr-1"></i> Beverages
                    </button>
                </div>
            </div>

            <!-- Menu Content -->
            <main>
                <section id="regular" class="menu-section">
                    <?php
                    if (isset($categories['pizza']['sections'][1])) {
                        echo renderSection($categories['pizza']['sections'][1], true);
                    }
                    ?>
                </section>

                <section id="special" class="menu-section hidden">
                    <?php
                    if (isset($categories['pizza']['sections'][0])) {
                        echo renderSection($categories['pizza']['sections'][0], true);
                    }
                    ?>
                </section>

                <section id="deals" class="menu-section hidden">
                    <?php
                    if (isset($categories['deals'])) {
                        echo renderCategory($categories['deals']);
                    }
                    ?>
                </section>

                <section id="burgers" class="menu-section hidden">
                    <?php
                    if (isset($categories['burgers'])) {
                        echo renderCategory($categories['burgers']);
                    }
                    ?>
                </section>

                <section id="fries" class="menu-section hidden">
                    <?php
                    if (isset($categories['fries'])) {
                        echo renderCategory($categories['fries']);
                    }
                    ?>
                </section>

                <section id="chicken" class="menu-section hidden">
                    <?php
                    if (isset($categories['chicken'])) {
                        echo renderCategory($categories['chicken']);
                    }
                    ?>
                </section>

                <section id="platters" class="menu-section hidden">
                    <?php
                    if (isset($categories['platters'])) {
                        echo renderCategory($categories['platters']);
                    }
                    ?>
                </section>

                <section id="pasta" class="menu-section hidden">
                    <?php
                    if (isset($categories['others']['sections'])) {
                        foreach ($categories['others']['sections'] as $section) {
                            if ($section['title'] === 'PASTA') {
                                echo renderSection($section, true);
                            }
                        }
                    }
                    ?>
                </section>

                <section id="sandwich" class="menu-section hidden">
                    <?php
                    if (isset($categories['others']['sections'])) {
                        foreach ($categories['others']['sections'] as $section) {
                            if ($section['title'] === 'Sandwich') {
                                echo renderSection($section, true);
                            }
                        }
                    }
                    ?>
                </section>

                <section id="wraps" class="menu-section hidden">
                    <?php
                    if (isset($categories['wraps'])) {
                        echo renderCategory($categories['wraps']);
                    }
                    ?>
                </section>

                <section id="paratha" class="menu-section hidden">
                    <?php
                    if (isset($categories['paratha'])) {
                        echo renderCategory($categories['paratha']);
                    }
                    ?>
                </section>

                <section id="shawarma" class="menu-section hidden">
                    <?php
                    if (isset($categories['shawarma'])) {
                        echo renderCategory($categories['shawarma']);
                    }
                    ?>
                </section>

                <section id="extras" class="menu-section hidden">
                    <?php
                    if (isset($categories['others']['sections'])) {
                        $isFirst = true;
                        foreach ($categories['others']['sections'] as $section) {
                            if ($section['title'] === 'Calzone Chunks' || $section['title'] === 'Pizza Toppings (Extra)') {
                                echo renderSection($section, $isFirst);
                                $isFirst = false;
                            }
                        }
                    }
                    ?>
                </section>

                <section id="beverages" class="menu-section hidden">
                    <?php
                    if (isset($categories['beverages'])) {
                        echo renderCategory($categories['beverages']);
                    }
                    ?>
                </section>
            </main>
        </div>
    </section>

    <div class="section-divider"></div>

    <!-- ═══════════════════════════════════════ -->
    <!-- 5. COMBO DEALS / HOT DEALS             -->
    <!-- ═══════════════════════════════════════ -->
    <section id="hot-deals" class="bg-[#1a1a1a] py-14 px-4">
        <div class="max-w-7xl mx-auto">

            <div class="text-center mb-10">
                <p class="text-[#f5f5f5]/50 font-semibold text-sm uppercase tracking-widest mb-2">Limited Time Offers</p>
                <h2 class="text-3xl md:text-4xl font-black text-[#FF671C]">
                    <i class="fas fa-fire mr-2 text-[#FFC700]"></i> PizzaG Hot Deals!
                </h2>
                <p class="text-[#FFC700] font-semibold mt-2">Grab these amazing combos before they're gone!</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                <!-- Deal Card 1 -->
                <div class="bg-[#111111] rounded-2xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C]/60 transition-all duration-300 shadow-xl">
                    <img src="offer/1.png" alt="PizzaG Special Deal 1" class="w-full h-auto">
                    <div class="p-4 flex flex-col sm:flex-row gap-3">
                        <a href="tel:03058223131"
                           class="flex-1 cta-phone text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-phone-alt"></i> 0305-8223131
                        </a>
                        <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order%20Deal%201" target="_blank"
                           class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fab fa-whatsapp"></i> Order on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Deal Card 2 -->
                <div class="bg-[#111111] rounded-2xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C]/60 transition-all duration-300 shadow-xl">
                    <img src="offer/2.png" alt="PizzaG Special Deal 2" class="w-full h-auto">
                    <div class="p-4 flex flex-col sm:flex-row gap-3">
                        <a href="tel:03058223131"
                           class="flex-1 cta-phone text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-phone-alt"></i> 0305-8223131
                        </a>
                        <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order%20Deal%202" target="_blank"
                           class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fab fa-whatsapp"></i> Order on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Deal Card 3 -->
                <div class="bg-[#111111] rounded-2xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C]/60 transition-all duration-300 shadow-xl">
                    <img src="offer/3.png" alt="PizzaG Special Deal 3" class="w-full h-auto">
                    <div class="p-4 flex flex-col sm:flex-row gap-3">
                        <a href="tel:03058223131"
                           class="flex-1 cta-phone text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-phone-alt"></i> 0305-8223131
                        </a>
                        <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order%20Deal%203" target="_blank"
                           class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fab fa-whatsapp"></i> Order on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Deal Card 4 -->
                <div class="bg-[#111111] rounded-2xl overflow-hidden border border-[#FF671C]/20 hover:border-[#FF671C]/60 transition-all duration-300 shadow-xl">
                    <img src="offer/4.png" alt="PizzaG Special Deal 4" class="w-full h-auto">
                    <div class="p-4 flex flex-col sm:flex-row gap-3">
                        <a href="tel:03058223131"
                           class="flex-1 cta-phone text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-phone-alt"></i> 0305-8223131
                        </a>
                        <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order%20Deal%204" target="_blank"
                           class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-3 px-4 rounded-xl transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fab fa-whatsapp"></i> Order on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 6. FULL MENU PREVIEW                   -->
    <!-- ═══════════════════════════════════════ -->
    <section class="bg-[#f5f5f5] py-14 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#0a0a0a]/50 font-semibold text-sm uppercase tracking-widest mb-2">Browse Everything</p>
                <h2 class="text-3xl md:text-4xl font-black text-[#0a0a0a]">
                    Scan Our Complete Menu Below
                </h2>
                <p class="text-[#0a0a0a]/60 mt-2">Tap any image to zoom in</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl overflow-hidden shadow-xl border border-[#FF671C]/10">
                    <img src="menu-a.jpg" alt="PizzaG Complete Menu Part 1"
                         onclick="openMenuModal()"
                         class="w-full h-auto cursor-pointer hover:scale-[1.02] transition-transform duration-300">
                </div>
                <div class="bg-white rounded-2xl overflow-hidden shadow-xl border border-[#FF671C]/10">
                    <img src="menu-b.jpg" alt="PizzaG Complete Menu Part 2"
                         onclick="openMenuModal()"
                         class="w-full h-auto cursor-pointer hover:scale-[1.02] transition-transform duration-300">
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 7. DELIVERY AREAS                      -->
    <!-- ═══════════════════════════════════════ -->
    <section id="delivery-areas" class="bg-[#0a0a0a] py-14 px-4">
        <div class="max-w-7xl mx-auto">

            <div class="text-center mb-10">
                <p class="text-[#f5f5f5]/40 font-semibold text-sm uppercase tracking-widest mb-2">Where We Deliver</p>
                <h2 class="text-3xl md:text-4xl font-black text-[#FF671C]">
                    <i class="fas fa-truck mr-2"></i> We Deliver To:
                </h2>
                <p class="text-[#f5f5f5]/55 mt-3 text-base max-w-xl mx-auto">
                    PizzaG delivers to Kacha Khuh and surrounding areas including Gulshan Colony, Peer Colony, Hussain Chowk, and more.
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <?php
                $areas = [
                    'Kacha Khuh','Gulshan Colony','Peer Colony','21/10-R','22/10-R',
                    '23/10-R','24/10-R','25/10-R','Hussain Chowk','19 Gharbi',
                    '19 Sharqi','Sabir Chowk','Allah Ditta Chowk','16/9-R','Tibba Muhammad Pur',
                    'Jannat Pur','30/10-R','34/10-R','38/10-R','Jahangeer Abad'
                ];
                foreach ($areas as $area):
                ?>
                <div class="area-card bg-[#111111] rounded-xl px-4 py-3 text-center cursor-pointer">
                    <span class="text-[#f5f5f5] font-semibold text-sm"><?= htmlspecialchars($area) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 8. FOOTER                              -->
    <!-- ═══════════════════════════════════════ -->
    <footer class="bg-[#0a0a0a] border-t-2 border-[#FF671C] pt-12 pb-4 px-4">
        <div class="max-w-7xl mx-auto">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">

                <!-- Brand Column -->
                <div class="md:col-span-1">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="icons/pizzag-logo.png" alt="PizzaG" class="h-12 w-auto object-contain">
                        <div>
                            <div class="font-black text-[#f5f5f5] text-lg">PizzaG</div>
                            <div class="text-[#FFC700] text-[10px] font-semibold">Delight in Every Bite...!</div>
                        </div>
                    </div>
                    <p class="text-[#f5f5f5]/50 text-sm leading-relaxed">Premium fast food at Kacha Khuh Railway Station. Fresh, bold, and delivered fast.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-[#f5f5f5] font-bold text-base mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition">Home</a></li>
                        <li><a href="#menu" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition">Menu</a></li>
                        <li><a href="#about-us" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition">About Us</a></li>
                        <li><a href="#hot-deals" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition">Hot Deals</a></li>
                        <li><a href="#delivery-areas" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition">Delivery Areas</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-[#f5f5f5] font-bold text-base mb-4">Contact Info</h4>
                    <div class="space-y-3 text-sm">
                        <p>
                            <a href="tel:03058223131" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition flex items-center gap-2">
                                <i class="fas fa-phone text-[#FF671C]"></i> 0305-8223131
                            </a>
                        </p>
                        <p>
                            <a href="tel:03018223131" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition flex items-center gap-2">
                                <i class="fas fa-phone text-[#FF671C]"></i> 0301-8223131
                            </a>
                        </p>
                        <p>
                            <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order" target="_blank"
                               class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-500"></i> Chat on WhatsApp
                            </a>
                        </p>
                        <p>
                            <a href="mailto:pizzag@gmail.com" class="text-[#f5f5f5]/60 hover:text-[#FF671C] transition flex items-center gap-2">
                                <i class="fas fa-envelope text-[#FF671C]"></i> pizzag@gmail.com
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Location + App -->
                <div>
                    <h4 class="text-[#f5f5f5] font-bold text-base mb-4">Our Location</h4>
                    <p class="text-[#f5f5f5]/60 text-sm leading-relaxed mb-4">
                        <i class="fas fa-map-marker-alt text-[#FF671C] mr-1"></i>
                        Kacha Khuh Railway Station<br>
                        Kacha Khuh, Punjab
                    </p>

                    <h4 class="text-[#f5f5f5] font-bold text-base mb-3">Install Our App</h4>
                    <div class="flex flex-col space-y-2">
                        <button onclick="installPWA('android')" id="footer-install-android"
                                class="inline-flex items-center bg-[#111111] border border-[#f5f5f5]/20 hover:border-[#FF671C] text-[#f5f5f5] font-bold px-4 py-2.5 rounded-xl transition text-sm gap-3">
                            <i class="fab fa-google-play text-xl text-green-400"></i>
                            <div class="text-left">
                                <span class="text-[10px] block text-[#f5f5f5]/50">GET IT ON</span>
                                <span class="text-sm">Google Play</span>
                            </div>
                        </button>
                        <button onclick="installPWA('ios')" id="footer-install-ios"
                                class="inline-flex items-center bg-[#111111] border border-[#f5f5f5]/20 hover:border-[#FF671C] text-[#f5f5f5] font-bold px-4 py-2.5 rounded-xl transition text-sm gap-3">
                            <i class="fab fa-apple text-xl text-[#f5f5f5]"></i>
                            <div class="text-left">
                                <span class="text-[10px] block text-[#f5f5f5]/50">DOWNLOAD ON THE</span>
                                <span class="text-sm">App Store</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-[#f5f5f5]/10 pt-6 text-center">
                <p class="text-[#f5f5f5]/40 text-sm">
                    &copy; 2026 <span class="text-[#FF671C] font-bold">PizzaG</span>. All rights reserved. | Delight in Every Bite...!
                </p>
                <p class="text-[#f5f5f5]/25 text-xs mt-1">
                    Powered by <a href="https://younasdev.com/about" target="_blank" rel="noopener" class="hover:text-[#FF671C] transition">YounusDev</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════ -->
    <!-- BOTTOM NAVIGATION (Mobile)             -->
    <!-- ═══════════════════════════════════════ -->
    <nav class="fixed bottom-0 left-0 right-0 bg-[#0a0a0a] border-t-2 border-[#FF671C]/40 z-50">
        <div class="flex justify-around items-center py-2 max-w-lg mx-auto">
            <a href="#" class="flex flex-col items-center text-[#f5f5f5]/50 hover:text-[#FF671C] transition px-3 py-1">
                <i class="fas fa-home text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Home</span>
            </a>
            <a href="#hot-deals" class="flex flex-col items-center text-[#FF671C] hover:text-orange-400 transition px-3 py-1">
                <i class="fas fa-fire text-xl"></i>
                <span class="text-[10px] mt-1 font-bold">Deals</span>
            </a>
            <button onclick="openMenuModal()" class="flex flex-col items-center text-[#FFC700] hover:text-yellow-300 px-3 py-1 nav-btn active">
                <i class="fas fa-utensils text-xl"></i>
                <span class="text-[10px] mt-1 font-bold">Menu</span>
            </button>
            <a href="https://wa.me/923058223131?text=Hi%20PizzaG%20I%20want%20to%20order" target="_blank"
               class="flex flex-col items-center text-green-500 hover:text-green-400 transition px-3 py-1">
                <i class="fab fa-whatsapp text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Order</span>
            </a>
            <a href="#delivery-areas" class="flex flex-col items-center text-[#f5f5f5]/50 hover:text-[#FF671C] transition px-3 py-1">
                <i class="fas fa-map-marker-alt text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Areas</span>
            </a>
            <a href="tel:03058223131" class="flex flex-col items-center text-[#f5f5f5]/50 hover:text-[#FF671C] transition px-3 py-1">
                <i class="fas fa-phone text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Call</span>
            </a>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════ -->
    <!-- MENU IMAGES MODAL                      -->
    <!-- ═══════════════════════════════════════ -->
    <div id="menuModal" class="fixed inset-0 bg-[#0a0a0a] z-[60]" style="display:none;">
        <div class="relative w-full h-full flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[#FF671C]/30">
                <h3 class="text-[#FF671C] text-xl font-bold">PizzaG Menu</h3>
                <button onclick="closeMenuModal()"
                        class="text-white text-2xl bg-[#FF671C] w-10 h-10 rounded-full hover:bg-orange-600 transition flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-4 max-w-2xl mx-auto pb-8">
                    <img src="menu-a.jpg" alt="PizzaG Menu Part 1" class="w-full rounded-xl border border-[#FF671C]/30">
                    <img src="menu-b.jpg" alt="PizzaG Menu Part 2" class="w-full rounded-xl border border-[#FF671C]/30">
                </div>
            </div>
        </div>
    </div>

    <!-- Single Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black/96 z-[70] flex items-center justify-center" style="display:none;">
        <button onclick="closeImageModal()"
                class="absolute top-4 right-4 text-white text-2xl z-10 bg-[#FF671C] w-11 h-11 rounded-full hover:bg-orange-600 transition flex items-center justify-center shadow-lg">
            <i class="fas fa-times"></i>
        </button>
        <div class="p-4 max-w-4xl max-h-[90vh] overflow-auto">
            <img id="modalImage" src="" alt="PizzaG Menu" class="w-full h-auto rounded-xl">
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- SCRIPTS                                -->
    <!-- ═══════════════════════════════════════ -->
    <script>
        function showCategory(category, btn) {
            document.querySelectorAll('.menu-section').forEach(s => s.classList.add('hidden'));
            document.getElementById(category).classList.remove('hidden');

            document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
        }

        function orderWhatsapp(item) {
            const message = `Hi PizzaG! I would like to order: ${item}`;
            window.open(`https://wa.me/923058223131?text=${encodeURIComponent(message)}`, '_blank');
        }

        function openMenuModal() {
            document.getElementById('menuModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeMenuModal() {
            document.getElementById('menuModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { closeMenuModal(); closeImageModal(); }
        });

        document.getElementById('menuModal').addEventListener('click', function(e) {
            if (e.target === this) closeMenuModal();
        });

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeImageModal();
        });

        function installPWA(platform) {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            if (platform === 'ios' || isIOS) {
                const iosBanner = document.getElementById('ios-install-banner');
                if (iosBanner) {
                    iosBanner.classList.remove('hidden');
                    iosBanner.classList.add('pwa-banner-animate');
                }
            } else {
                // Always show banner — forceShowBanner bypasses installed/dismissed guards
                if (window.PWAInstall && typeof window.PWAInstall.forceShowBanner === 'function') {
                    window.PWAInstall.forceShowBanner();
                } else {
                    const installBanner = document.getElementById('pwa-install-banner');
                    if (installBanner) {
                        installBanner.classList.remove('hidden');
                        installBanner.classList.add('pwa-banner-animate');
                    }
                }
            }
        }
    </script>

    <!-- PWA Install Banners -->
    <div id="pwa-install-banner" class="hidden">
        <button id="close-install-banner" aria-label="Close"><i class="fas fa-times"></i></button>
        <div class="pwa-banner-content">
            <img src="icons/pizzag-logo.png" alt="PizzaG" class="pwa-banner-icon">
            <div class="pwa-banner-text">
                <p class="pwa-banner-title">Install PizzaG</p>
                <p class="pwa-banner-subtitle">Add to home screen for quick access</p>
            </div>
            <button id="pwa-install-btn"><i class="fas fa-download"></i> Install</button>
        </div>
    </div>

    <div id="ios-install-banner" class="hidden">
        <button id="close-ios-banner" aria-label="Close"><i class="fas fa-times"></i></button>
        <div class="ios-banner-content">
            <p class="ios-banner-title"><i class="fas fa-mobile-alt"></i> Install PizzaG App</p>
            <div class="ios-banner-steps">
                <div class="ios-step">
                    <span class="ios-step-number">1</span>
                    <span class="ios-step-text">Tap the Share button <span class="ios-share-icon"><i class="fas fa-share-from-square"></i></span></span>
                </div>
                <div class="ios-step">
                    <span class="ios-step-number">2</span>
                    <span class="ios-step-text">Scroll and tap <strong>"Add to Home Screen"</strong> <i class="fas fa-plus-square ios-step-icon"></i></span>
                </div>
            </div>
        </div>
    </div>

    <script src="pwa-install.js"></script>
</body>
</html>
