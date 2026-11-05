<?php
require __DIR__ . '/backend/bootstrap.php';
$settings = activeance_settings($pdo);

try {
    $avgRating = (float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE approved=1")->fetchColumn();
    $totalReviews = (int) $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved=1")->fetchColumn();
} catch (Exception $e) {
    $avgRating = 0;
    $totalReviews = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews | <?= htmlspecialchars($settings['business_name']) ?></title>

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
                dark:    '<?= htmlspecialchars($settings['color_dark']) ?>',
                red:     '<?= htmlspecialchars($settings['color_primary']) ?>',
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
            <a href="index.php" class="text-slate-300 hover:text-white transition-colors">Home</a>
            <a href="about.php" class="text-slate-300 hover:text-white transition-colors">About</a>
            <a href="review.php" class="text-brand-red font-bold">Reviews</a>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['phone']) ?>" target="_blank"
               class="bg-brand-whatsapp hover:opacity-90 text-white font-bold py-2 px-5 rounded-full transition-all">
                Contact
            </a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="relative z-10 h-screen overflow-hidden">
        <div id="hero-slider" class="absolute inset-0 bg-brand-dark"></div>

        <div id="hero-overlay"
             class="absolute inset-0 z-[1] pointer-events-none opacity-0 transition-opacity duration-700"
             style="background: linear-gradient(180deg, rgba(11,19,43,0.94) 0%, rgba(11,19,43,0.58) 30%, rgba(11,19,43,0.52) 60%, rgba(11,19,43,0.92) 85%, rgba(11,19,43,1.00) 100%);"></div>

        <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4 pt-24 pointer-events-none">
            <p class="text-brand-red font-bold tracking-[0.3em] uppercase mb-4 text-sm hero-fade">
                Client Feedback
            </p>
            <h1 class="text-5xl md:text-7xl font-black mb-6 hero-fade leading-tight">
                WHAT OUR <span class="text-brand-red drop-shadow-[0_0_15px_rgba(214,40,40,0.5)]">CLIENTS</span> SAY
            </h1>
            
            <div class="hero-fade flex flex-col items-center gap-2 mt-4">
                <div class="flex items-center gap-2 text-3xl text-yellow-400">
                    <?php 
                    $fullStars = round($avgRating);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $fullStars ? '★' : '☆';
                    }
                    ?>
                </div>
                <p class="text-slate-300 tracking-wider">
                    <?= $totalReviews > 0 ? number_format($avgRating, 1) . ' / 5 based on ' . $totalReviews . ' reviews' : 'No reviews yet. Be the first!' ?>
                </p>
            </div>
        </div>

        <div id="hero-dots" class="absolute bottom-32 left-0 right-0 z-10 flex justify-center gap-2 pointer-events-auto"></div>

        <!-- Interactive Scroll Mouse -->
        <a href="#review-form" 
           class="absolute bottom-10 left-1/2 -translate-x-1/2 z-20 group cursor-pointer flex items-center justify-center w-10 h-16 rounded-full border-2 border-white/20 bg-white/5 backdrop-blur-sm transition-all duration-300 hover:border-brand-red/60 hover:bg-white/10 hover:shadow-[0_0_20px_rgba(214,40,40,0.4)] hover:-translate-y-1">
            <div class="w-4 h-8 rounded-full border-2 border-white/30 group-hover:border-brand-red/70 flex justify-center pt-1.5 transition-colors duration-300">
                <div class="w-1 h-1.5 bg-white/70 group-hover:bg-brand-red rounded-full animate-scroll-wheel"></div>
            </div>
        </a>
    </section>

    <!-- REVIEW FORM -->
    <section id="review-form" class="relative z-10 py-16 px-6 max-w-2xl mx-auto">
        <div class="liquid-glass p-8 md:p-12 rounded-3xl scroll-reveal">
            <h2 class="text-2xl md:text-3xl font-black mb-6">Leave a Review</h2>

            <div id="formMessage" class="hidden mb-6 p-4 rounded-xl text-sm font-bold"></div>

            <form id="reviewForm" class="flex flex-col gap-5">
                <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

                <div>
                    <label class="text-sm text-slate-400 uppercase tracking-widest mb-2 block">Your Name</label>
                    <input type="text" name="name" required maxlength="100" placeholder="e.g. John D."
                           class="w-full p-3 rounded-xl bg-white/5 border border-white/15 text-white focus:border-brand-red outline-none transition">
                </div>

                <div>
                    <label class="text-sm text-slate-400 uppercase tracking-widest mb-2 block">Your Rating</label>
                    <div class="flex gap-2" id="starRating">
                        <button type="button" class="star text-4xl text-slate-600 hover:text-yellow-400 transition" data-value="1">★</button>
                        <button type="button" class="star text-4xl text-slate-600 hover:text-yellow-400 transition" data-value="2">★</button>
                        <button type="button" class="star text-4xl text-slate-600 hover:text-yellow-400 transition" data-value="3">★</button>
                        <button type="button" class="star text-4xl text-slate-600 hover:text-yellow-400 transition" data-value="4">★</button>
                        <button type="button" class="star text-4xl text-slate-600 hover:text-yellow-400 transition" data-value="5">★</button>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="0" required>
                </div>

                <div>
                    <label class="text-sm text-slate-400 uppercase tracking-widest mb-2 block">Your Comment</label>
                    <textarea name="comment" required maxlength="1000" minlength="10" rows="4" placeholder="Tell us about your experience..."
                              class="w-full p-3 rounded-xl bg-white/5 border border-white/15 text-white focus:border-brand-red outline-none transition resize-none"></textarea>
                </div>

                <button type="submit" 
                        class="mt-2 bg-brand-red hover:opacity-90 text-white font-bold py-4 px-8 rounded-full transition-all shadow-[0_0_20px_rgba(214,40,40,0.4)] w-fit disabled:opacity-50 disabled:cursor-not-allowed">
                    Submit Review
                </button>
            </form>
        </div>
    </section>

    <!-- REVIEWS LIST -->
    <section class="relative z-10 py-16 px-6 max-w-5xl mx-auto pb-32">
        <h2 class="text-3xl md:text-4xl font-black text-center mb-12 scroll-reveal">Recent Feedback</h2>
        <div id="reviewsContainer" class="grid md:grid-cols-2 gap-6"></div>
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

    <!-- SLIDESHOW SCRIPT -->
    <script>
    (async function heroSlideshow() {
        const heroSlider  = document.getElementById('hero-slider');
        const heroDots    = document.getElementById('hero-dots');
        const heroOverlay = document.getElementById('hero-overlay');
        if (!heroSlider) return;

        let cars = [];
        try {
            const res  = await fetch('backend/api.php?action=get_cars');
            const data = await res.json();
            if (Array.isArray(data)) cars = data;
        } catch (err) { console.warn('Slideshow: could not load cars.', err); }
        if (!cars.length) return;

        const escapeHtml = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const featured = cars.slice(0, 6);
        heroSlider.innerHTML = '';

        featured.forEach((car, i) => {
            const slide = document.createElement('div');
            slide.className = 'hero-slide' + (i === 0 ? ' current' : '');
            slide.innerHTML = `<img src="${escapeHtml(car.image_url)}" alt="${escapeHtml(car.make_model)}" loading="${i === 0 ? 'eager' : 'lazy'}" onerror="this.style.display='none'">`;
            heroSlider.appendChild(slide);
        });

        const slides = Array.from(heroSlider.querySelectorAll('.hero-slide'));
        if (heroOverlay) heroOverlay.style.opacity = '1';

        heroDots.innerHTML = '';
        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
            dot.addEventListener('click', () => goTo(i));
            heroDots.appendChild(dot);
        });

        let current = 0, timer = null, hovered = false;
        const INTERVAL = 5000;

        function start() { clearInterval(timer); if (slides.length > 1 && !hovered) timer = setInterval(next, INTERVAL); }
        function next() { goTo((current + 1) % slides.length); }
        function goTo(index) {
            if (index === current || !slides.length) return;
            const prev = current;
            slides[prev].classList.remove('current');
            slides[prev].classList.add('past');
            slides[index].classList.add('current');
            heroDots.querySelectorAll('.hero-dot').forEach((d, i) => d.classList.toggle('active', i === index));
            current = index;
            setTimeout(() => {
                const leaving = slides[prev];
                leaving.style.transition = 'none';
                leaving.classList.remove('past');
                void leaving.offsetHeight;
                leaving.style.transition = '';
            }, 1150);
            start();
        }
        heroSlider.addEventListener('mouseenter', () => { hovered = true;  clearInterval(timer); });
        heroSlider.addEventListener('mouseleave', () => { hovered = false; start(); });
        document.addEventListener('visibilitychange', () => { if (document.hidden) clearInterval(timer); else if (!hovered) start(); });
        start();
    })();
    </script>

    <!-- REVIEWS LOGIC -->
    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingValue');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value);
                ratingInput.value = val;
                stars.forEach(s => {
                    if (parseInt(s.dataset.value) <= val) {
                        s.classList.remove('text-slate-600'); s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400'); s.classList.add('text-slate-600');
                    }
                });
            });
        });

        const form = document.getElementById('reviewForm');
        const formMessage = document.getElementById('formMessage');
        const submitBtn = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (ratingInput.value === '0') {
                formMessage.className = 'mb-6 p-4 rounded-xl text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30';
                formMessage.textContent = 'Please select a star rating.';
                formMessage.classList.remove('hidden');
                return;
            }

            formMessage.classList.add('hidden');
            const formData = new FormData(form);
            const data = { name: formData.get('name'), rating: formData.get('rating'), comment: formData.get('comment') };

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const res = await fetch('backend/api.php?action=submit_review', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    form.reset();
                    ratingInput.value = '0';
                    stars.forEach(s => { s.classList.remove('text-yellow-400'); s.classList.add('text-slate-600'); });
                    formMessage.className = 'mb-6 p-4 rounded-xl text-sm font-bold bg-green-500/20 text-green-400 border border-green-500/30';
                    formMessage.textContent = '✅ Thank you! Your review has been submitted and is pending approval.';
                } else {
                    formMessage.className = 'mb-6 p-4 rounded-xl text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30';
                    formMessage.textContent = '❌ ' + (result.error || 'Something went wrong.');
                }
            } catch (err) {
                formMessage.className = 'mb-6 p-4 rounded-xl text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30';
                formMessage.textContent = '❌ Network error. Please try again.';
            }
            formMessage.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        });

        async function loadReviews() {
            const container = document.getElementById('reviewsContainer');
            try {
                const res = await fetch('backend/api.php?action=get_reviews');
                const reviews = await res.json();
                container.innerHTML = '';

                if (!reviews || reviews.length === 0) {
                    container.innerHTML = '<p class="col-span-full text-center text-slate-400">No approved reviews yet. Check back soon!</p>';
                    return;
                }

                reviews.forEach(review => {
                    const date = new Date(review.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const starsHtml = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);

                    const card = document.createElement('div');
                    card.className = 'liquid-glass-card p-6 rounded-2xl scroll-reveal';
                    card.innerHTML = `
                        <div class="flex items-center justify-between mb-3">
                            <strong class="text-lg">${escapeHTML(review.name)}</strong>
                            <span class="text-yellow-400 text-lg">${starsHtml}</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">${nl2br(escapeHTML(review.comment))}</p>
                        <p class="text-slate-500 text-xs mt-4">${date}</p>
                    `;
                    container.appendChild(card);
                });
                ScrollTrigger.refresh();
            } catch (err) {
                console.error(err);
                container.innerHTML = '<p class="col-span-full text-center text-red-400">Failed to load reviews.</p>';
            }
        }

        function escapeHTML(str) { return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function nl2br(str) { return str.replace(/\n/g, '<br>'); }

        loadReviews();
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>

    <!-- 3D BACKGROUND -->
    <script type="module">
        import * as THREE from 'three';
        const canvas   = document.getElementById('webgl-canvas');
        const scene    = new THREE.Scene();
        const camera   = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.z = 8;

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);

        const group = new THREE.Group();
        const ring1 = new THREE.Mesh(new THREE.TorusGeometry(3, 0.02, 16, 100), new THREE.MeshStandardMaterial({ color: 0xD62828, emissive: 0xD62828, emissiveIntensity: 2 }));
        const ring2 = new THREE.Mesh(new THREE.TorusGeometry(4, 0.02, 16, 100), new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffffff, emissiveIntensity: 1 }));
        ring2.rotation.x = Math.PI / 2;
        group.add(ring1, ring2);
        group.position.z = -5;
        scene.add(group);

        scene.add(new THREE.AmbientLight(0xffffff, 0.5));
        const dirLight = new THREE.DirectionalLight(0xffffff, 1);
        dirLight.position.set(10, 10, 5);
        scene.add(dirLight);

        let mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
        });

        (function animate() {
            requestAnimationFrame(animate);
            camera.position.x += (mouseX * 1.5 - camera.position.x) * 0.05;
            camera.position.y += (mouseY * 1.5 - camera.position.y) * 0.05;
            camera.lookAt(scene.position);
            group.rotation.x += 0.002;
            group.rotation.y += 0.003;
            renderer.render(scene, camera);
        })();
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        gsap.registerPlugin(ScrollTrigger);
        gsap.from('.hero-fade', { y: 50, opacity: 0, duration: 1, stagger: 0.2, ease: 'power3.out' });
        gsap.utils.toArray('.scroll-reveal').forEach(el => {
            gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 85%' }, y: 50, opacity: 0, duration: 1, ease: 'power3.out' });
        });
    </script>
    <!-- 3D Card Tilt Effect -->
    <script>
        document.querySelectorAll('.liquid-glass, .liquid-glass-card, .car-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left, y = e.clientY - rect.top;
                const cX = rect.width / 2, cY = rect.height / 2;
                card.style.transform = `perspective(1000px) rotateX(${((y - cY) / cY) * -5}deg) rotateY(${((x - cX) / cX) * 5}deg) scale3d(1.02, 1.02, 1.02)`;
                card.style.boxShadow = `0 20px 40px rgba(0,0,0,0.4), 0 0 20px rgba(214,40,40,0.2)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
                card.style.boxShadow = '';
            });
        });
    </script>
</body>
</html>