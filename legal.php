<?php
require __DIR__ . '/backend/bootstrap.php';
$settings = activeance_settings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal &amp; Privacy | <?= htmlspecialchars($settings['business_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: {
                dark:    '<?= htmlspecialchars($settings['color_dark']) ?>',
                red:     '<?= htmlspecialchars($settings['color_primary']) ?>',
                whatsapp:'<?= htmlspecialchars($settings['color_accent']) ?>'
            }}}}
        }
    </script>
</head>
<body class="bg-brand-dark text-white font-sans min-h-screen flex flex-col">

    <nav class="px-6 py-4 flex items-center justify-between bg-brand-dark/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-50">
        <a href="index.php" class="flex items-center gap-3">
            <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="Logo" class="h-10 w-auto" onerror="this.style.display='none'">
            <span class="font-black tracking-widest text-sm hidden sm:inline"><?= htmlspecialchars($settings['business_name']) ?></span>
        </a>
        <a href="index.php" class="text-sm text-slate-300 hover:text-white transition-colors">&larr; Back to Home</a>
    </nav>

    <main class="flex-grow max-w-4xl mx-auto px-6 py-16">
        <h1 class="text-4xl font-black mb-8 text-brand-red">Privacy Policy &amp; Terms of Service</h1>
        
        <div class="space-y-8 text-slate-300 leading-relaxed">
            <section>
                <h2 class="text-2xl font-bold text-white mb-3">1. Company Information</h2>
                <p>This website is operated by <strong><?= htmlspecialchars($settings['business_name']) ?></strong>. We are a registered Close Corporation in the Republic of Namibia.</p>
                <p class="mt-2">We operate as an <strong>online vehicle dealership</strong> based in the Erongo Region, serving customers across all 14 regions of Namibia.</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li><strong>Operational Base:</strong> Walvis Bay, Erongo Region, Namibia</li>
                    <li><strong>Contact Number:</strong> <?= htmlspecialchars($settings['phone']) ?></li>
                    <li><strong>Business Hours:</strong> Online enquiries 24/7 (Responses during standard business hours)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-white mb-3">2. Vehicle Sales &amp; Nationwide Delivery</h2>
                <p>All vehicles listed on this website are subject to availability. While we strive for 100% accuracy, specifications, prices, and images may be subject to change without notice. All vehicles are sold with transparent history checks.</p>
                <p class="mt-2">As an online dealership, we facilitate orders from any part of Namibia. Delivery arrangements, timelines, and associated transport costs will be agreed upon between the buyer and <?= htmlspecialchars($settings['business_name']) ?> prior to finalizing the sale. Any warranty or guarantee provided will be stipulated in the final Bill of Sale.</p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-white mb-3">3. Privacy Policy (Data Protection)</h2>
                <p>We respect your privacy and comply with applicable data protection laws. We only collect personal information (such as your name, phone number, and comments) when you voluntarily submit a review or contact us via WhatsApp.</p>
                <p class="mt-2">We do not sell, rent, or lease your personal information to third parties. Your data is strictly used to facilitate your vehicle inquiries, arrange nationwide delivery, and improve our online service.</p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-white mb-3">4. Cookies &amp; Tracking</h2>
                <p>This website uses essential session cookies strictly to maintain admin login states. We do not use malicious third-party tracking pixels.</p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-white mb-3">5. Pricing &amp; VAT</h2>
                <p>All prices displayed are in Namibian Dollars (N$). <strong>Prices are inclusive of 15% VAT.</strong> (Please contact us directly to confirm if delivery fees to your specific region are excluded from the listed price).</p>
            </section>
        </div>
    </main>

    <footer class="py-8 text-center text-slate-500 border-t border-white/10 bg-black/20">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['business_name']) ?>. All rights reserved.</p>
        <p class="text-xs mt-1">Walvis Bay, Erongo Region, Namibia</p>
        <a href="index.php" class="text-xs mt-2 inline-block text-slate-600 hover:text-brand-red transition-colors">Return to Home</a>
    </footer>
</body>
</html>