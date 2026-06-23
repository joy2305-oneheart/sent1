// ===== HERO SECTION ANIMATIONS =====

/**
 * Matches CSS --homie-motion-scale intent (viewport-relative motion distances).
 */
function homieMotionScale() {
    const w = window.innerWidth || document.documentElement.clientWidth;
    return Math.min(1.05, Math.max(0.75, w / 1200));
}

function homieAssetUrl(path) {
    const base = typeof oneHomieHome !== 'undefined' && oneHomieHome.assetsBase
        ? oneHomieHome.assetsBase.replace(/\/$/, '')
        : '';
    if (base) {
        return base + (path.startsWith('/') ? path : '/' + path);
    }
    return path.startsWith('/') ? path : '/' + path;
}

// Smooth scroll behavior for navigation
function handleSmoothScroll(e, targetId) {
    e.preventDefault();
    const element = document.getElementById(targetId);
    
    if (element) {
        const headerEl = document.getElementById('header');
        const headerOffset = headerEl ? headerEl.getBoundingClientRect().height + 16 : 100;
        const elementPosition = element.getBoundingClientRect().top + window.scrollY;
        const offsetPosition = elementPosition - headerOffset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });

        // Close mobile menu if open
        const mobileNav = document.getElementById('mobile-nav');
        mobileNav.style.display = 'none';
    }
}

function handleLogoClick(e) {
    e.preventDefault();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Mobile menu toggle
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            if (mobileNav.style.display === 'none' || !mobileNav.style.display) {
                mobileNav.style.display = 'flex';
            } else {
                mobileNav.style.display = 'none';
            }
        });
    }
}

// Header scroll effect
function initHeaderScroll() {
    const header = document.getElementById('header');
    let isScrolled = false;

    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY;
        const shouldBeScrolled = scrollPosition > 10;

        if (shouldBeScrolled !== isScrolled) {
            isScrolled = shouldBeScrolled;
            if (isScrolled) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    }, { passive: true });
}

// Hero section scroll animations
function initHeroScrollAnimation() {
    let rafId;
    let currentProgress = 0;

    const heroBannerFrame = document.querySelector('.homie-hero-banner__frame');
    const heroTextOverlay = document.querySelector('.hero-background-text');

    function handleScroll() {
        const scrollY = window.scrollY;
        const ms = homieMotionScale();
        const maxScroll = 400 * ms;
        const targetProgress = Math.min(scrollY / maxScroll, 1);

        const easeOutQuad = (t) => t * (2 - t);
        const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

        const smoothUpdate = () => {
            currentProgress += (targetProgress - currentProgress) * 0.1;

            if (Math.abs(targetProgress - currentProgress) > 0.001) {
                const scale = 1 - easeOutQuad(currentProgress) * 0.15;
                const borderRadius = 16 + easeOutCubic(currentProgress) * 32 * ms;
                const heightVh = 100 - easeOutQuad(currentProgress) * 37.5;

                if (heroBannerFrame) {
                    heroBannerFrame.style.transform = `scale(${scale})`;
                    heroBannerFrame.style.borderRadius = `${borderRadius}px`;
                    heroBannerFrame.style.height = `${heightVh}vh`;
                }

                if (heroTextOverlay) {
                    heroTextOverlay.style.transform = `translateY(${currentProgress * 150 * ms}px)`;
                    heroTextOverlay.style.opacity = 1 - currentProgress * 0.8;
                }

                rafId = requestAnimationFrame(smoothUpdate);
            } else {
                const scale = 1 - easeOutQuad(targetProgress) * 0.15;
                const borderRadius = 16 + easeOutCubic(targetProgress) * 32 * ms;
                const heightVh = 100 - easeOutQuad(targetProgress) * 37.5;

                if (heroBannerFrame) {
                    heroBannerFrame.style.transform = `scale(${scale})`;
                    heroBannerFrame.style.borderRadius = `${borderRadius}px`;
                    heroBannerFrame.style.height = `${heightVh}vh`;
                }

                if (heroTextOverlay) {
                    heroTextOverlay.style.transform = `translateY(${targetProgress * 150 * ms}px)`;
                    heroTextOverlay.style.opacity = 1 - targetProgress * 0.8;
                }
            }
        };

        cancelAnimationFrame(rafId);
        smoothUpdate();
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleScroll, { passive: true });
}

// ===== STATS SECTION =====

function useCountUp(end, duration = 2000, suffix = '') {
    let count = 0;
    let startTime = null;

    const animate = (currentTime) => {
        if (!startTime) startTime = currentTime;
        const progress = Math.min((currentTime - startTime) / duration, 1);

        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        count = Math.floor(easeOutQuart * end);

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };

    return {
        value: count + suffix,
        start: () => requestAnimationFrame(animate),
        getCount: () => count
    };
}

function initStatsSection() {
    const statsSection = document.getElementById('stats-section');
    if (!statsSection) return;

    let hasAnimated = false;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !hasAnimated) {
            hasAnimated = true;
            animateStats();
        }
    }, { threshold: 0.3 });

    observer.observe(statsSection);
}

function animateStats() {
    const homes = useCountUp(15, 2000, 'K+');
    const cities = useCountUp(120, 2000, '');
    const users = useCountUp(50, 2000, 'K+');

    const statItems = document.querySelectorAll('.stat-item');

    homes.start();
    cities.start();
    users.start();

    let lastUpdate = 0;
    const updateInterval = 50;

    const updateDisplay = () => {
        const now = Date.now();
        if (now - lastUpdate >= updateInterval) {
            if (statItems[0]) {
                const h = homes.getCount();
                statItems[0].querySelector('.stat-value').textContent = h >= 15 ? '15K+' : `${h}K+`;
            }
            if (statItems[1]) {
                statItems[1].querySelector('.stat-value').textContent = String(cities.getCount());
            }
            if (statItems[2]) {
                const u = users.getCount();
                statItems[2].querySelector('.stat-value').textContent = u >= 50 ? '50K+' : `${u}K+`;
            }
            lastUpdate = now;
        }

        if (homes.getCount() < 15 || cities.getCount() < 120 || users.getCount() < 50) {
            requestAnimationFrame(updateDisplay);
        }
    };

    updateDisplay();
}

// ===== PRICING SECTION - CAROUSEL =====

function initPricingCarousel() {
    const properties = [
        {
            propertyName: "Sunset Beach Villa",
            location: "Malibu, California",
            duration: "Min. 3 nights",
            availableDate: "Available now",
            image: homieAssetUrl("property-beach-villa.jpg"),
            pricePerNight: 450,
            rating: 4.9,
        },
        {
            propertyName: "Mountain Retreat Cabin",
            location: "Aspen, Colorado",
            duration: "Min. 2 nights",
            availableDate: "Dec 15 - Jan 30",
            image: homieAssetUrl("property-mountain-cabin.jpg"),
            pricePerNight: 320,
            rating: 4.8,
        },
        {
            propertyName: "Downtown Luxury Loft",
            location: "New York City, NY",
            duration: "Min. 1 night",
            availableDate: "Available now",
            image: homieAssetUrl("property-city-loft.jpg"),
            pricePerNight: 280,
            rating: 4.7,
        },
        {
            propertyName: "Tuscan Countryside Estate",
            location: "Florence, Italy",
            duration: "Min. 4 nights",
            availableDate: "Available now",
            image: homieAssetUrl("property-tuscan-estate.jpg"),
            pricePerNight: 520,
            rating: 4.9,
        },
        {
            propertyName: "Tropical Paradise Bungalow",
            location: "Bali, Indonesia",
            duration: "Min. 2 nights",
            availableDate: "Available now",
            image: homieAssetUrl("property-tropical-bungalow.jpg"),
            pricePerNight: 180,
            rating: 4.8,
        },
        {
            propertyName: "Lakefront Modern House",
            location: "Lake Tahoe, California",
            duration: "Min. 3 nights",
            availableDate: "Year-round",
            image: homieAssetUrl("property-lakefront-modern.jpg"),
            pricePerNight: 380,
            rating: 4.9,
        },
    ];

    const carousel = document.getElementById('pricing-carousel');
    if (!carousel) return;

    // Triple the properties for infinite loop effect
    const tripleProperties = [...properties, ...properties, ...properties];

    tripleProperties.forEach(prop => {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
            <img src="${prop.image}" alt="${prop.propertyName}" class="property-image">
            <div class="property-content">
                <div class="property-header">
                    <div class="property-info">
                        <div class="property-name">${prop.propertyName}</div>
                        <div class="property-location">${prop.location}</div>
                    </div>
                    <div class="property-rating">★ ${prop.rating}</div>
                </div>
                <div class="property-meta">
                    <div>
                        <div class="property-duration">${prop.duration}</div>
                        <div class="property-date">${prop.availableDate}</div>
                    </div>
                    <div class="property-price">$${prop.pricePerNight}</div>
                </div>
            </div>
        `;
        carousel.appendChild(card);
    });

    let isHovered = false;
    let position = 0;
    let lastTime = performance.now();

    carousel.addEventListener('mouseenter', () => {
        isHovered = true;
    });

    carousel.addEventListener('mouseleave', () => {
        isHovered = false;
    });

    function animate(currentTime) {
        const deltaTime = currentTime - lastTime;
        lastTime = currentTime;

        const speed = isHovered ? 0.3 : 1;
        position += speed * (deltaTime / 16);

        const totalWidth = carousel.scrollWidth / 3;

        if (position >= totalWidth) {
            position = 0;
        }

        carousel.style.transform = `translateX(-${position}px)`;
        requestAnimationFrame(animate);
    }

    requestAnimationFrame(animate);
}

// ===== TESTIMONIALS SECTION =====

function initTestimonials() {
    const testimonials = [
        {
            name: "Marie Dupont",
            role: "Owner in Nice",
            content: "I rented out my apartment in less than a week. The interface is so intuitive!",
            avatar: "/placeholder.svg?height=48&width=48",
        },
        {
            name: "Thomas Martin",
            role: "Tenant in Paris",
            content: "Finally a transparent platform. I found my studio without paying agency fees.",
            avatar: "/placeholder.svg?height=48&width=48",
        },
        {
            name: "Sophie Bernard",
            role: "Owner in Lyon",
            content: "The tenant verification system gives me peace of mind. I recommend it 100%!",
            avatar: "/placeholder.svg?height=48&width=48",
        },
    ];

    const testimonials2 = [
        {
            name: "Lucas Petit",
            role: "Tenant in Bordeaux",
            content: "Best rental experience I've ever had. The process was seamless from start to finish.",
            avatar: "/placeholder.svg?height=48&width=48",
        },
        {
            name: "Emma Laurent",
            role: "Owner in Marseille",
            content: "My property was listed and rented within days. Incredible platform!",
            avatar: "/placeholder.svg?height=48&width=48",
        },
        {
            name: "Antoine Rousseau",
            role: "Tenant in Toulouse",
            content: "No hidden fees, no surprises. Exactly what I was looking for in a rental app.",
            avatar: "/placeholder.svg?height=48&width=48",
        },
    ];

    const row1 = document.getElementById('testimonials-row-1');
    const row2 = document.getElementById('testimonials-row-2');

    function createTestimonialElement(testimonial) {
        const div = document.createElement('div');
        div.className = 'testimonial';
        div.innerHTML = `
            <div class="testimonial-content">
                <div class="testimonial-avatar"></div>
                <p class="testimonial-text">"${testimonial.content}"</p>
            </div>
            <div class="testimonial-footer">
                <div class="testimonial-name">${testimonial.name}</div>
                <div class="testimonial-role">${testimonial.role}</div>
            </div>
        `;
        return div;
    }

    // Triple testimonials for infinite loop
    const tripleTestimonials = [...testimonials, ...testimonials, ...testimonials];
    const tripleTestimonials2 = [...testimonials2, ...testimonials2, ...testimonials2];

    tripleTestimonials.forEach(t => row1.appendChild(createTestimonialElement(t)));
    tripleTestimonials2.forEach(t => row2.appendChild(createTestimonialElement(t)));

    let isPaused = false;
    let row1Position = 0;
    let row2Position = 0;
    let lastTime = performance.now();

    row1.addEventListener('mouseenter', () => { isPaused = true; });
    row1.addEventListener('mouseleave', () => { isPaused = false; });
    row2.addEventListener('mouseenter', () => { isPaused = true; });
    row2.addEventListener('mouseleave', () => { isPaused = false; });

    function animate(currentTime) {
        if (!isPaused) {
            const deltaTime = currentTime - lastTime;
            lastTime = currentTime;

            row1Position += (deltaTime / 16) * 1;
            row2Position -= (deltaTime / 16) * 1;

            const totalWidth1 = row1.scrollWidth / 3;
            const totalWidth2 = row2.scrollWidth / 3;

            if (row1Position >= totalWidth1) {
                row1Position = 0;
            }

            if (row2Position <= -totalWidth2) {
                row2Position = 0;
            }

            row1.style.transform = `translateX(-${row1Position}px)`;
            row2.style.transform = `translateX(${row2Position}px)`;
        }

        requestAnimationFrame(animate);
    }

    requestAnimationFrame(animate);
}

// ===== FAQ SECTION =====

function initFAQ() {
    const faqs = [
        {
            question: "How do I post a listing on Homie?",
            answer: "It's very simple! Create an account, click 'Post a listing', add photos and a description of your property, set the price and availability. Your listing will be live within minutes after verification.",
        },
        {
            question: "What are the fees for owners?",
            answer: "Homie charges a 3% commission only when a rental is confirmed. No listing fees, no mandatory subscription. The Pro plan at $49/month reduces the commission to 2% for multi-property owners.",
        },
        {
            question: "How are tenants verified?",
            answer: "Each tenant must provide an ID and proof of income. We verify these documents and assign a trust score. Owners can view the complete profile before accepting a request.",
        },
        {
            question: "Are payments secure?",
            answer: "Yes, all payments go through our secure platform. Funds are held until check-in confirmation, then released to the owner. In case of disputes, our team intervenes to find a solution.",
        },
        {
            question: "What does the damage insurance cover?",
            answer: "Our included insurance covers material damage up to $5,000 per rental. It protects owners against accidental damage. A $200 deductible applies in case of a claim.",
        },
        {
            question: "Can I cancel a reservation?",
            answer: "Cancellation conditions are set by each owner (flexible, moderate, or strict). Refunds are calculated based on these conditions. Force majeure cases may qualify for a full refund.",
        },
    ];

    const faqList = document.getElementById('faq-list');
    if (!faqList) return;

    faqs.forEach((faq) => {
        const item = document.createElement('div');
        item.className = 'faq-item';
        item.innerHTML = `
            <div class="faq-question">
                <span>${faq.question}</span>
                <div class="faq-toggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="faq-answer">${faq.answer}</div>
        `;
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        item.addEventListener('click', () => toggleFAQ(item));
        item.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                toggleFAQ(item);
            }
        });
        faqList.appendChild(item);
    });
}

function toggleFAQ(faqItem) {
    const isOpen = faqItem.classList.contains('open');
    const answer = faqItem.querySelector('.faq-answer');

    // Single-open accordion: close every item before toggling the clicked one
    document.querySelectorAll('.faq-item').forEach((item) => {
        item.classList.remove('open');
        const itemAnswer = item.querySelector('.faq-answer');
        if (itemAnswer) {
            itemAnswer.style.maxHeight = '0';
        }
    });

    if (!isOpen) {
        faqItem.classList.add('open');
        if (answer) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        }
    }
}

// ===== SCROLL REVEAL ANIMATIONS =====

function initScrollReveal() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all sections and cards
    document.querySelectorAll('.stat-item, .service-card, .testimonial, .property-card').forEach(el => {
        observer.observe(el);
    });
}

// ===== INITIALIZATION =====

function init() {
    window.handleSmoothScroll = handleSmoothScroll;
    window.handleLogoClick = handleLogoClick;

    // Init all components
    initMobileMenu();
    initHeaderScroll();
    initHeroScrollAnimation();
    initStatsSection();
    initPricingCarousel();
    initTestimonials();
    initFAQ();
    initScrollReveal();
}

// Run initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Handle animations on page load
window.addEventListener('load', () => {
    // Ensure hero elements are visible on load
    const heroTitleContainer = document.querySelector('.hero-title-container');
    if (heroTitleContainer) {
        setTimeout(() => {
            heroTitleContainer.style.opacity = '1';
            heroTitleContainer.style.transform = 'translateY(0)';
        }, 100);
    }
});
