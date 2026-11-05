<?php
require __DIR__ . '/backend/bootstrap.php';
$settings = activeance_settings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['business_name']) ?> | Premium Vehicles</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <script type="importmap">
        { "imports": { "three": "https://unpkg.com/three@0.160.0/build/three.module.js" } }
    </script>

    <link rel="stylesheet" href="style.css">

    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: {
                dark:   '<?= htmlspecialchars($settings['color_dark']) ?>',
                red:    '<?= htmlspecialchars($settings['color_primary']) ?>',
                whatsapp:'<?= htmlspecialchars($settings['color_accent']) ?>'
            }}}}
        }
    </script>
    <style>
        @keyframes scroll-wheel {
            0%   { transform: translateY(0);    opacity: 1; }
            50%  { transform: translateY(6px);  opacity: 0.5; }
            100% { transform: translateY(10px); opacity: 0; }
        }
        .animate-scroll-wheel {
            animation: scroll-wheel 1.8s cubic-bezier(0.15, 0.41, 0.69, 0.94) infinite;
        }
    </style>
</head>
<body class="bg-brand-dark text-white overflow-x-hidden font-sans">

    <canvas id="webgl-canvas" class="fixed inset-0 z-0 pointer-events-none"></canvas>

    <!-- NAVIGATION -->
    <nav class="fixed top-0 left-0 right-0 z-20 px-6 py-4 flex items-center justify-between bg-brand-dark/40 backdrop-blur-md border-b border-white/10">
        <a href="index.php" class="flex items-center gap-3">
            <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="h-10 w-auto" onerror="this.style.display='none'">
            <span class="font-black tracking-widest text-sm hidden sm:inline"><?= htmlspecialchars($settings['business_name']) ?></span>
        </a>
        <div class="flex items-center gap-6 text-sm">
            <a href="index.php" class="text-brand-red font-bold">Home</a>
            <a href="about.php" class="text-slate-300 hover:text-white transition-colors">About</a>
            <a href="review.php" class="text-slate-300 hover:text-white transition-colors">Reviews</a>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['phone']) ?>" target="_blank"
               class="bg-brand-whatsapp hover:opacity-90 text-white font-bold py-2 px-5 rounded-full transition-all">
                Contact
            </a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="relative z-10 h-screen overflow-hidden">
        <!-- Slideshow background -->
        <div id="hero-slider" class="absolute inset-0 bg-brand-dark"></div>

        <!-- Dark gradient overlay -->
        <div id="hero-overlay"
             class="absolute inset-0 z-[1] pointer-events-none opacity-0 transition-opacity duration-700"
             style="background: linear-gradient(180deg, rgba(11,19,43,0.92) 0%, rgba(11,19,43,0.55) 30%, rgba(11,19,43,0.50) 60%, rgba(11,19,43,0.90) 85%, rgba(11,19,43,1.00) 100%);"></div>

        <!-- Static Content Container -->
        <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4 pt-24 pointer-events-none">
            <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo"
                 class="w-64 md:w-96 mb-6 hero-fade drop-shadow-[0_0_30px_rgba(255,255,255,0.2)]"
                 onerror="this.style.display='none'">
            
            <!-- INTERACTIVE SCROLL MOUSE (Links to Car List) -->
            <a href="#car-list" 
               class="pointer-events-auto mb-8 group cursor-pointer flex items-center justify-center w-10 h-16 rounded-full border-2 border-white/20 bg-white/5 backdrop-blur-sm transition-all duration-300 hover:border-brand-red/60 hover:bg-white/10 hover:shadow-[0_0_20px_rgba(214,40,40,0.4)] hover:-translate-y-1 hero-fade">
                <div class="w-4 h-8 rounded-full border-2 border-white/30 group-hover:border-brand-red/70 flex justify-center pt-1.5 transition-colors duration-300">
                    <div class="w-1 h-1.5 bg-white/70 group-hover:bg-brand-red rounded-full animate-scroll-wheel"></div>
                </div>
            </a>

            <h1 class="text-5xl md:text-7xl font-black mb-4 hero-fade">
                LIMITED COLLECTION <br>
                <span class="text-brand-red drop-shadow-[0_0_15px_rgba(214,40,40,0.6)]">FOR SALE</span>
            </h1>
            
            <p class="text-xl md:text-2xl text-slate-300 tracking-widest font-light hero-fade">
                <?= htmlspecialchars($settings['tagline']) ?>
            </p>
            <?php if (!empty($settings['quote_text'])): ?>
                <p class="text-slate-400 italic mt-4 text-lg hero-fade">"<?= htmlspecialchars($settings['quote_text']) ?>"</p>
            <?php endif; ?>
        </div>

        <!-- Slide dots -->
        <div id="hero-dots" class="absolute bottom-10 left-0 right-0 z-10 flex justify-center gap-2 pointer-events-auto"></div>
    </section>

    <!-- CARS LIST SECTION -->
    <section id="car-list" class="relative z-10 py-20 px-6 max-w-7xl mx-auto flex flex-col gap-24">
        <div class="text-center text-slate-400">Loading collection...</div>
    </section>

    <!-- OWNER BIO SECTION -->
    <section class="relative z-10 py-24 px-6 max-w-5xl mx-auto">
        <div class="liquid-glass p-8 md:p-12 rounded-3xl flex flex-col md:flex-row items-center gap-10 scroll-reveal">
            <div class="w-48 h-48 md:w-64 md:h-64 shrink-0 rounded-full overflow-hidden border-4 border-brand-red shadow-[0_0_30px_rgba(214,40,40,0.4)]">
                <img src="<?= htmlspecialchars($settings['owner_photo_url']) ?>" alt="<?= htmlspecialchars($settings['owner_name']) ?>"
                     class="w-full h-full object-cover"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($settings['owner_name']) ?>&background=0B132B&color=fff&size=256'">
            </div>
            <div class="text-center md:text-left">
                <h2 class="text-brand-red font-bold tracking-widest uppercase mb-2 text-sm">Meet the Owner</h2>
                <h3 class="text-4xl font-black mb-4"><?= htmlspecialchars($settings['owner_name']) ?></h3>
                <p class="text-slate-300 leading-relaxed text-lg"><?= nl2br(htmlspecialchars($settings['owner_bio'])) ?></p>
                <a href="about.php" class="inline-flex items-center gap-2 mt-6 text-brand-red font-bold hover:text-white transition-colors">
                    Read Full Story
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
    </section>

     <!-- FOOTER -->
    <footer class="relative z-10 py-8 text-center text-slate-500 border-t border-white/10 bg-black/20 backdrop-blur-sm">
        <p>© <span id="year"></span> <?= htmlspecialchars($settings['business_name']) ?>. All rights reserved.</p>
        
        <!-- Legal Requirements -->
        <p class="text-xs mt-2">Contact: <?= htmlspecialchars($settings['phone']) ?> | Address: Walvis Bay</p>
        <p class="text-[10px] mt-1 text-slate-600">All prices are inclusive of 15% VAT.</p>
        
        <div class="flex items-center justify-center gap-6 mt-4 text-xs">
            <a href="about.php" class="text-slate-500 hover:text-brand-red transition-colors">About</a>
            <span class="text-slate-700">•</span>
            <a href="review.php" class="text-slate-500 hover:text-brand-red transition-colors">Reviews</a>
            <span class="text-slate-700">•</span>
            <a href="legal.php" class="text-slate-500 hover:text-brand-red transition-colors">Privacy &amp; Terms</a>
            <span class="text-slate-700">•</span>
            <a href="admin.php" class="text-slate-600 hover:text-brand-red transition-colors">Admin Portal</a>
        </div>
    </footer>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['phone']) ?>" target="_blank" 
       class="fixed bottom-6 right-6 z-50 bg-brand-whatsapp text-white p-4 rounded-full shadow-[0_4px_20px_rgba(37,211,102,0.4)] hover:scale-110 transition-transform duration-300">
        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>

    <script type="module" src="app.js"></script>
</body>
</html>