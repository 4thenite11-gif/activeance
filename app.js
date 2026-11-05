import * as THREE from 'three';

// ==========================================
// 1. THREE.JS 3D BACKGROUND (Mouse Reactive)
// ==========================================
const canvas = document.getElementById('webgl-canvas');
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
camera.position.z = 8;

const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);

const group = new THREE.Group();
const ring1 = new THREE.Mesh(
    new THREE.TorusGeometry(3, 0.02, 16, 100),
    new THREE.MeshStandardMaterial({ color: 0xD62828, emissive: 0xD62828, emissiveIntensity: 2 })
);
const ring2 = new THREE.Mesh(
    new THREE.TorusGeometry(4, 0.02, 16, 100),
    new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffffff, emissiveIntensity: 1 })
);
ring2.rotation.x = Math.PI / 2;
group.add(ring1, ring2);
group.position.z = -5;
scene.add(group);

scene.add(new THREE.AmbientLight(0xffffff, 0.5));
const dirLight = new THREE.DirectionalLight(0xffffff, 1);
dirLight.position.set(10, 10, 5);
scene.add(dirLight);

// Mouse Tracking Variables
let mouseX = 0;
let mouseY = 0;
document.addEventListener('mousemove', (event) => {
    mouseX = (event.clientX / window.innerWidth) * 2 - 1;
    mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
});

(function animate() {
    requestAnimationFrame(animate);
    
    // Parallax effect for 3D Rings
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

// ==========================================
// 2. HELPERS & 3D CARD TILT
// ==========================================
function escapeHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function waPhone(phone) {
    return String(phone || '').replace(/\D/g, '') || '264814540060';
}

function apply3DTilt() {
    document.querySelectorAll('.car-card, .liquid-glass').forEach(card => {
        // Remove old listeners to avoid duplicates
        card.replaceWith(card.cloneNode(true)); 
    });

    document.querySelectorAll('.car-card, .liquid-glass').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -5;
            const rotateY = ((x - centerX) / centerX) * 5;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
            card.style.boxShadow = `0 20px 40px rgba(0,0,0,0.4), 0 0 20px rgba(214,40,40,0.2)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
            card.style.boxShadow = '';
        });
    });
}

// ==========================================
// 3. GSAP SCROLL ANIMATIONS
// ==========================================
gsap.registerPlugin(ScrollTrigger);
gsap.from(".hero-fade", { y: 50, opacity: 0, duration: 1, stagger: 0.2, ease: "power3.out" });
gsap.utils.toArray('.scroll-reveal').forEach(element => {
    gsap.from(element, {
        scrollTrigger: { trigger: element, start: "top 85%" },
        y: 50, opacity: 0, duration: 1, ease: "power3.out"
    });
});

// ==========================================
// 4. HERO SLIDESHOW (Right to Left)
// ==========================================
function buildHeroSlideshow(cars) {
    const heroSlider  = document.getElementById('hero-slider');
    const heroDots    = document.getElementById('hero-dots');
    const heroOverlay = document.getElementById('hero-overlay');
    if (!heroSlider || !cars || cars.length === 0) return;

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
}

// ==========================================
// 5. FETCH DATA & BOOT
// ==========================================
const carListContainer = document.getElementById('car-list');
let SETTINGS = { business_name: 'Activeance Investment CC', phone: '+264814540060' };

async function fetchSettings() {
    try {
        const res  = await fetch('backend/api.php?action=get_settings');
        const data = await res.json();
        if (data && !data.error) SETTINGS = { ...SETTINGS, ...data };
    } catch (err) { console.warn('Could not load settings.'); }
}

async function fetchCars() {
    try {
        const response = await fetch('backend/api.php?action=get_cars');
        const cars = await response.json();
        return Array.isArray(cars) ? cars : [];
    } catch (err) { return []; }
}

function renderCarList(cars) {
    carListContainer.innerHTML = '';

    if (cars.length === 0) {
        carListContainer.innerHTML = '<p class="text-center text-slate-400">No cars available at the moment.</p>';
        return;
    }

    cars.forEach((car, index) => {
        const formattedPrice = parseFloat(car.price).toLocaleString();
        const whatsappNumber = waPhone(SETTINGS.phone);
        const businessName   = SETTINGS.business_name || 'Activeance Investment CC';
        const message = encodeURIComponent(`Hi ${businessName}, I am interested in the ${car.year} ${car.make_model} for N$${formattedPrice}.`);
        const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${message}`;
        const isEven = index % 2 === 0;

        const cardHTML = `
            <div class="flex flex-col ${isEven ? 'md:flex-row' : 'md:flex-row-reverse'} items-center gap-8 car-card scroll-reveal">
                <div class="w-full md:w-1/2 liquid-glass-card p-2 rounded-2xl">
                    <img src="${escapeHtml(car.image_url)}" alt="${escapeHtml(car.make_model)}" class="rounded-xl w-full h-[300px] md:h-[400px] object-cover" onerror="this.src='public/car1.jpg'">
                </div>
                <div class="w-full md:w-1/2 flex flex-col gap-4">
                    <h2 class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-slate-200 to-slate-400">${escapeHtml(car.make_model)}</h2>
                    <p class="text-2xl text-slate-300">${escapeHtml(car.year)} &bull; <span class="text-white font-bold">N$${formattedPrice}</span></p>
                    <div class="flex items-center gap-2 text-green-400 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Inspected, Polished & Ready to Impress</span>
                    </div>
                    <a href="${whatsappUrl}" target="_blank" rel="noopener" class="mt-4 flex items-center justify-center gap-2 bg-brand-whatsapp hover:opacity-90 text-white font-bold py-4 px-8 rounded-full transition-all w-fit shadow-[0_0_20px_rgba(37,211,102,0.4)]">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Shop Now via WhatsApp
                    </a>
                </div>
            </div>
        `;
        carListContainer.insertAdjacentHTML('beforeend', cardHTML);
    });

    apply3DTilt(); // Apply hover tilt to newly added cards
    ScrollTrigger.refresh(); // Re-trigger animations
}

(async function init() {
    await fetchSettings();
    const cars = await fetchCars();
    
    renderCarList(cars);
    buildHeroSlideshow(cars); // Restore the slideshow!

    document.getElementById('year').textContent = new Date().getFullYear();
})();