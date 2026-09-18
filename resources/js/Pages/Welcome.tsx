import { useEffect } from 'react'
import { Link } from '@inertiajs/react'

export default function Welcome() {
    useEffect(() => {
        // Inject Bootstrap Icons stylesheet
        if (!document.getElementById('bootstrap-icons')) {
            const biLink = document.createElement('link')
            biLink.rel = 'stylesheet'
            biLink.href =
                'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css'
            biLink.id = 'bootstrap-icons'
            document.head.appendChild(biLink)
        }

        // Inject Google Fonts
        if (!document.getElementById('welcome-fonts')) {
            const fontLink = document.createElement('link')
            fontLink.rel = 'stylesheet'
            fontLink.href =
                'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap'
            fontLink.id = 'welcome-fonts'
            document.head.appendChild(fontLink)
        }

        // Header scroll effect
        const header = document.getElementById('main-header')
        const hero = document.getElementById('home')
        let heroFramePending = false

        function updateHeroOnScroll() {
            if (!hero) return
            const progress = Math.min(window.scrollY / 520, 1)
            const maxGutter =
                window.innerWidth <= 600 ? 12 : Math.min(window.innerWidth * 0.035, 52)
            const radius = window.innerWidth <= 600 ? 22 : 34
            hero.style.setProperty('--hero-gutter', `${maxGutter * progress}px`)
            hero.style.setProperty('--hero-radius', `${radius * progress}px`)
            heroFramePending = false
        }

        function onScroll() {
            if (!header) return
            if (window.scrollY > 50) {
                header.classList.add('scrolled')
            } else {
                header.classList.remove('scrolled')
            }
            if (!heroFramePending) {
                window.requestAnimationFrame(updateHeroOnScroll)
                heroFramePending = true
            }
        }

        window.addEventListener('scroll', onScroll)
        window.addEventListener('resize', updateHeroOnScroll)
        updateHeroOnScroll()

        // Hero slider
        const slides = document.querySelectorAll<HTMLElement>('.lp-hero-slide')
        let currentSlide = 0
        const slideInterval = 6000

        function nextSlide() {
            slides[currentSlide].classList.remove('active')
            currentSlide = (currentSlide + 1) % slides.length
            slides[currentSlide].classList.add('active')
        }

        const sliderTimer = setInterval(nextSlide, slideInterval)

        return () => {
            window.removeEventListener('scroll', onScroll)
            window.removeEventListener('resize', updateHeroOnScroll)
            clearInterval(sliderTimer)
        }
    }, [])

    return (
        <>
            <style>{`
                /* --- Design Tokens (Landing Page) --- */
                .lp-wrap {
                    --lp-page-start: #CFE3FF;
                    --lp-page-mid: #EAF3FF;
                    --lp-page-end: #FFFFFF;
                    --lp-text: #1B1F27;
                    --lp-text2: #636B78;
                    --lp-blue: #2F6FED;
                    --lp-shadow-soft: 0 2px 10px rgba(16,20,30,.08);
                    --lp-shadow-strong: 0 10px 30px rgba(16,20,30,.16);
                    --lp-radius-hero: 22px;
                    --lp-radius-pill: 100px;
                    --font-display: "Plus Jakarta Sans", system-ui, sans-serif;
                    --font-ui: "Inter", system-ui, sans-serif;

                    font-family: var(--font-ui);
                    color: var(--lp-text);
                    background: linear-gradient(180deg, var(--lp-page-start) 0%, var(--lp-page-mid) 38%, var(--lp-page-end) 72%);
                    background-repeat: no-repeat;
                    background-size: cover;
                    min-height: 100vh;
                    line-height: 1.6;
                    overflow-x: hidden;
                }

                .lp-wrap h1, .lp-wrap h2, .lp-wrap h3, .lp-wrap h4 {
                    font-family: var(--font-display);
                    line-height: 1.2;
                }

                .lp-wrap a { text-decoration: none; color: inherit; }

                /* Buttons */
                .lp-btn {
                    display: inline-block;
                    font-family: var(--font-ui);
                    font-weight: 700;
                    font-size: 15px;
                    padding: 14px 28px;
                    border-radius: var(--lp-radius-pill);
                    cursor: pointer;
                    transition: transform 0.2s cubic-bezier(.2,.8,.2,1), box-shadow 0.2s;
                    border: none;
                }
                .lp-btn-primary {
                    background-color: var(--lp-blue);
                    color: #FFFFFF;
                    box-shadow: var(--lp-shadow-strong);
                }
                .lp-btn-primary:hover { transform: translateY(-2px) scale(0.98); }
                .lp-btn-secondary {
                    background-color: #FFFFFF;
                    color: var(--lp-text);
                    box-shadow: var(--lp-shadow-soft);
                }

                /* --- Header --- */
                #main-header {
                    position: fixed;
                    top: 0; left: 0;
                    width: 100%;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 24px 5%;
                    background: transparent;
                    backdrop-filter: none;
                    box-shadow: none;
                    z-index: 1000;
                    transition: top 0.35s cubic-bezier(.2,.8,.2,1), left 0.35s, width 0.35s, padding 0.35s, transform 0.35s, border-radius 0.35s ease, background 0.3s ease, box-shadow 0.3s ease, backdrop-filter 0.3s ease;
                }
                .lp-logo {
                    font-family: var(--font-display);
                    font-weight: 800;
                    font-size: 24px;
                    color: #FFFFFF;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: color 0.3s ease, font-size 0.3s ease;
                }
                #main-header nav { display: flex; gap: 32px; }
                #main-header nav a {
                    font-weight: 600;
                    font-size: 15px;
                    color: rgba(255,255,255,0.9);
                    transition: color 0.2s;
                }
                #main-header nav a:hover { color: #FFFFFF; }

                /* Scrolled state */
                #main-header.scrolled {
                    top: 16px; left: 50%;
                    width: min(calc(100% - 48px), 1060px);
                    padding: 10px 18px;
                    transform: translateX(-50%);
                    border-radius: 999px;
                    background: rgba(255,255,255,0.9);
                    backdrop-filter: blur(12px);
                    box-shadow: 0 12px 34px rgba(16,20,30,.14);
                }
                #main-header.scrolled .lp-logo { font-size: 20px; color: var(--lp-blue); }
                #main-header.scrolled nav a { font-size: 14px; color: var(--lp-text2); }
                #main-header.scrolled nav a:hover { color: var(--lp-blue); }
                #main-header.scrolled .lp-btn { padding: 10px 20px; font-size: 14px; }

                /* --- Hero --- */
                .lp-hero {
                    position: relative;
                    width: calc(100% - (var(--hero-gutter, 0px) * 2));
                    height: 100vh;
                    min-height: 600px;
                    margin-inline: auto;
                    border-radius: var(--hero-radius, 0px);
                    overflow: hidden;
                    background-color: #000;
                    display: flex;
                    align-items: center;
                    transition: width .08s linear, border-radius .08s linear;
                }
                .lp-hero-overlay {
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0.7) 100%);
                    z-index: 2;
                }
                .lp-hero-slide {
                    position: absolute;
                    inset: 0;
                    opacity: 0;
                    transition: opacity 1.2s ease-in-out;
                    display: flex;
                    align-items: center;
                    padding: 0 5%;
                    z-index: 1;
                }
                .lp-hero-slide.active { opacity: 1; z-index: 3; }
                .lp-hero-bg {
                    position: absolute;
                    inset: 0;
                    width: 100%; height: 100%;
                    object-fit: cover;
                    z-index: -1;
                    transform: scale(1.05);
                    transition: transform 7s linear;
                }
                .lp-hero-slide.active .lp-hero-bg { transform: scale(1); }
                .lp-hero-content {
                    position: relative;
                    z-index: 4;
                    max-width: 600px;
                    color: #FFFFFF;
                    margin-top: 60px;
                }
                .lp-hero-content h1 { font-size: 56px; margin-bottom: 20px; }
                .lp-hero-content p { font-size: 18px; margin-bottom: 32px; opacity: 0.95; line-height: 1.7; }

                /* --- Services --- */
                .lp-services { padding: 100px 5%; text-align: center; }
                .lp-section-header { margin-bottom: 64px; }
                .lp-section-header h2 { font-size: 36px; margin-bottom: 16px; color: var(--lp-text); }
                .lp-section-header p { color: var(--lp-text2); font-size: 18px; max-width: 600px; margin: 0 auto; }
                .lp-services-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 32px;
                }
                .lp-service-card {
                    min-height: 342px;
                    padding: 40px 32px 32px;
                    border-radius: 24px;
                    text-align: left;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    position: relative;
                    transition: transform 0.3s ease, border-radius 0.3s ease;
                }
                .lp-service-card::after {
                    content: "";
                    position: absolute;
                    width: 180px; height: 180px;
                    right: -78px; bottom: -92px;
                    border-radius: 50%;
                    background: rgba(255,255,255,.14);
                    pointer-events: none;
                }
                .lp-service-card:hover { transform: translateY(-6px) rotate(-1deg); border-radius: 32px 20px 32px 20px; }
                .lp-service-icon {
                    width: 56px; height: 56px;
                    background: rgba(255,255,255,.7);
                    border-radius: 16px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 24px;
                    margin-bottom: 28px;
                }
                .lp-service-card h3 { max-width: 230px; font-size: 30px; line-height: 1.08; letter-spacing: -.5px; margin-bottom: 16px; }
                .lp-service-card p { max-width: 360px; font-size: 15px; line-height: 1.6; opacity: .82; margin-bottom: 22px; }
                .lp-service-kicker, .lp-service-link {
                    position: relative; z-index: 1;
                    font-size: 12px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase;
                }
                .lp-service-kicker { order: -1; margin-bottom: 14px; opacity: .68; }
                .lp-service-link {
                    display: inline-flex; align-items: center; gap: 8px;
                    margin-top: auto; letter-spacing: 0; text-transform: none; font-size: 14px;
                }
                .lp-service-link i { font-size: 17px; transition: transform .2s ease; }
                .lp-service-card:hover .lp-service-link i { transform: translateX(4px); }
                .lp-service-card:nth-child(1) { background: #A0ED25; color: #14200A; }
                .lp-service-card:nth-child(2) { background: #10192E; color: #FFFFFF; }
                .lp-service-card:nth-child(3) { background: #2F6FED; color: #FFFFFF; }

                /* --- Feature Split --- */
                .lp-feature-split {
                    display: flex; align-items: center; gap: 64px;
                    padding: 80px 5%;
                    background: #E8EDF3;
                    margin: 120px 5% 120px;
                    border-radius: var(--lp-radius-hero);
                    box-shadow: var(--lp-shadow-soft);
                    position: relative; isolation: isolate;
                }
                .lp-feature-split::before {
                    content: "";
                    position: absolute; inset: 0;
                    background: linear-gradient(115deg, #2F6FED 0%, #2F6FED 40%, #E8EDF3 60%, #E8EDF3 100%);
                    border-radius: inherit; z-index: -1;
                }
                .lp-feature-text { flex: 1; color: #FFFFFF; }
                .lp-feature-text h2 { font-size: 32px; margin-bottom: 24px; }
                .lp-feature-text p { color: rgba(255,255,255,.84); margin-bottom: 24px; font-size: 16px; }
                .lp-feature-list { list-style: none; margin-bottom: 32px; padding: 0; }
                .lp-feature-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; font-weight: 500; }
                .lp-feature-list li i { color: #FFFFFF; font-size: 20px; }
                .lp-feature-text .lp-btn-secondary { color: var(--lp-text); background: #FFFFFF; }
                .lp-feature-image { flex: 1; display: flex; justify-content: center; align-items: center; }
                .lp-feature-image img {
                    width: 100%; max-width: 320px;
                    aspect-ratio: 9 / 19.5; object-fit: cover;
                    border-radius: 36px; border: 10px solid #1B1F27;
                    box-shadow: var(--lp-shadow-strong);
                    margin-top: -140px; margin-bottom: -140px; margin-right: -40px;
                    transform: rotate(18deg);
                    filter: drop-shadow(19px 30px 10px rgba(9,11,17,0.28));
                    z-index: 10;
                }

                /* --- Bottom CTA --- */
                .lp-bottom-cta { text-align: center; padding: 120px 5%; }
                .lp-bottom-cta h2 { font-size: 40px; margin-bottom: 24px; }
                .lp-bottom-cta p { color: var(--lp-text2); font-size: 18px; margin-bottom: 40px; }

                /* --- Footer --- */
                .lp-footer { background: #FFFFFF; padding: 80px 5% 40px; border-top: 1px solid #EEF0F3; }
                .lp-footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 48px; margin-bottom: 64px; }
                .lp-footer-brand p { color: var(--lp-text2); margin-top: 16px; max-width: 300px; }
                .lp-footer-heading { font-family: var(--font-display); font-size: 18px; margin-bottom: 24px; color: var(--lp-text); }
                .lp-footer-links { list-style: none; padding: 0; }
                .lp-footer-links li { margin-bottom: 16px; }
                .lp-footer-links a { color: var(--lp-text2); transition: color 0.2s; }
                .lp-footer-links a:hover { color: var(--lp-blue); }
                .lp-contact-info li { display: flex; gap: 12px; color: var(--lp-text2); margin-bottom: 16px; }
                .lp-contact-info i { color: var(--lp-blue); font-size: 20px; }
                .lp-footer-bottom { text-align: center; padding-top: 32px; border-top: 1px solid #EEF0F3; color: var(--lp-text2); font-size: 14px; }

                /* --- Responsive --- */
                @media (max-width: 900px) {
                    .lp-feature-split {
                        flex-direction: column-reverse;
                        padding: 40px 5% 60px;
                        margin: 140px 5% 60px;
                        gap: 32px;
                    }
                    .lp-feature-split::before { background: linear-gradient(180deg, #2F6FED 0%, #2F6FED 40%, #E8EDF3 60%, #E8EDF3 100%); }
                    .lp-feature-text { color: var(--lp-text); }
                    .lp-feature-text h2 { color: #FFFFFF; }
                    .lp-feature-text p { color: var(--lp-text2); }
                    .lp-feature-list li i { color: var(--lp-blue); }
                    .lp-feature-image img { margin-right: 0; margin-bottom: 0; margin-top: -110px; transform: rotate(12deg); max-width: 220px; }
                    .lp-footer-grid { grid-template-columns: 1fr 1fr; }
                    .lp-hero-content h1 { font-size: 42px; }
                    #main-header nav { display: none; }
                    #main-header.scrolled { top: 10px; width: calc(100% - 24px); padding: 8px 12px; }
                    #main-header.scrolled .lp-logo { font-size: 18px; }
                    #main-header.scrolled .lp-btn { padding: 9px 14px; font-size: 12px; }
                }

                @media (max-width: 600px) {
                    .lp-footer-grid { grid-template-columns: 1fr; }
                    .lp-hero-content h1 { font-size: 36px; }
                }
            `}</style>

            <div className="lp-wrap">
                {/* Header */}
                <header id="main-header">
                    <div className="lp-logo">
                        <i className="bi bi-phone-flip"></i> AfProsPos
                    </div>
                    <nav>
                        <a href="#services">Repairs</a>
                        <a href="#shop">Shop Devices</a>
                        <a href="#trade-in">Trade-In</a>
                        <a href="#warranty">Warranty</a>
                    </nav>
                    <div>
                        <Link href="/customer/login" className="lp-btn lp-btn-primary">
                            Customer Portal
                        </Link>
                    </div>
                </header>

                {/* Full Bleed Rotating Hero Section */}
                <section className="lp-hero" id="home">
                    <div className="lp-hero-overlay"></div>

                    <div className="lp-hero-slide active">
                        <img
                            src="https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=1600&q=80"
                            alt="Phone Technician"
                            className="lp-hero-bg"
                        />
                        <div className="lp-hero-content">
                            <h1>Expert Diagnosis &amp; Phone Repair</h1>
                            <p>
                                We diagnose the condition of your device components and provide a structured
                                outcome before any repair work begins. Track your repair status online anytime.
                            </p>
                            <button className="lp-btn lp-btn-primary">Book a Repair</button>
                        </div>
                    </div>

                    <div className="lp-hero-slide">
                        <img
                            src="https://images.unsplash.com/photo-1616348436168-de43ad0db179?auto=format&fit=crop&w=1600&q=80"
                            alt="Tech Sales Expert"
                            className="lp-hero-bg"
                        />
                        <div className="lp-hero-content">
                            <h1>Premium Devices &amp; Accessories</h1>
                            <p>
                                Shop the latest devices. All our devices come with a configurable warranty
                                policy covering specific durations, exclusions, and guaranteed resolution options.
                            </p>
                            <button className="lp-btn lp-btn-primary">Browse Shop</button>
                        </div>
                    </div>

                    <div className="lp-hero-slide">
                        <img
                            src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1600&q=80"
                            alt="Customer Support"
                            className="lp-hero-bg"
                        />
                        <div className="lp-hero-content">
                            <h1>Upgrade &amp; Trade-In Instantly</h1>
                            <p>
                                Bring your existing device for a functional and cosmetic assessment. Receive
                                instant trade-in credit to apply toward your next device purchase.
                            </p>
                            <button className="lp-btn lp-btn-primary">Value My Device</button>
                        </div>
                    </div>
                </section>

                {/* Services Grid */}
                <section className="lp-services" id="services">
                    <div className="lp-section-header">
                        <h2>Everything Your Device Needs</h2>
                        <p>
                            From shattered screens to upgrading your daily driver, we offer a complete suite
                            of services backed by expert technicians and transparent pricing.
                        </p>
                    </div>
                    <div className="lp-services-grid">
                        <div className="lp-service-card">
                            <span className="lp-service-kicker">Repair module</span>
                            <div className="lp-service-icon"><i className="bi bi-tools"></i></div>
                            <h3>Transparent Repairs</h3>
                            <p>View your cost breakdown—parts and labour clearly separated. Make partial down payments and pay the balance upon collection.</p>
                            <span className="lp-service-link">Explore <i className="bi bi-arrow-right"></i></span>
                        </div>
                        <div className="lp-service-card">
                            <span className="lp-service-kicker">Protection module</span>
                            <div className="lp-service-icon"><i className="bi bi-shield-check"></i></div>
                            <h3>Reliable Warranties</h3>
                            <p>Whether you're buying a new phone or repairing an old one, benefit from our structured warranty system handling repairs, replacements, and refunds.</p>
                            <span className="lp-service-link">Explore <i className="bi bi-arrow-right"></i></span>
                        </div>
                        <div className="lp-service-card">
                            <span className="lp-service-kicker">Upgrade module</span>
                            <div className="lp-service-icon"><i className="bi bi-arrow-left-right"></i></div>
                            <h3>Device Trade-Ins</h3>
                            <p>Ready for an upgrade? We rigorously assess your phone's IMEI, condition, and functional state to provide a fair trade-in credit toward your next purchase.</p>
                            <span className="lp-service-link">Explore <i className="bi bi-arrow-right"></i></span>
                        </div>
                    </div>
                </section>

                {/* Split Feature Section */}
                <section className="lp-feature-split" id="tracking">
                    <div className="lp-feature-text">
                        <h2>Stay Informed at Every Step</h2>
                        <p>Say goodbye to calling the shop for updates. Our dedicated customer dashboard puts you in complete control of your devices.</p>
                        <ul className="lp-feature-list">
                            <li><i className="bi bi-check-circle-fill"></i> Track active repair jobs.</li>
                            <li><i className="bi bi-bell-fill"></i> Receive notifications at every significant status change.</li>
                            <li><i className="bi bi-receipt"></i> View your full purchase and repair history in one secure place.</li>
                            <li><i className="bi bi-gift-fill"></i> Refer friends using your unique link and earn rewards.</li>
                        </ul>
                        <Link href="/customer/register" className="lp-btn lp-btn-secondary">
                            Create Free Account
                        </Link>
                    </div>
                    <div className="lp-feature-image">
                        <img
                            src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80"
                            alt="Customer checking phone dashboard"
                        />
                    </div>
                </section>

                {/* Bottom CTA */}
                <section className="lp-bottom-cta">
                    <h2>Ready to restore your device?</h2>
                    <p>Join thousands of satisfied customers who trust AfProsPos with their technology.</p>
                    <button className="lp-btn lp-btn-primary" style={{ marginRight: '16px' }}>Book a Repair</button>
                    <button className="lp-btn lp-btn-secondary">Contact Support</button>
                </section>

                {/* Footer */}
                <footer className="lp-footer">
                    <div className="lp-footer-grid">
                        <div className="lp-footer-brand">
                            <div className="lp-logo" style={{ color: 'var(--lp-blue)' }}>
                                <i className="bi bi-phone-flip"></i> AfProsPos
                            </div>
                            <p>Your trusted destination for premium device sales, certified repairs, and transparent technology services.</p>
                        </div>
                        <div>
                            <h4 className="lp-footer-heading">Services</h4>
                            <ul className="lp-footer-links">
                                <li><a href="#">Phone Repair</a></li>
                                <li><a href="#">Shop Devices</a></li>
                                <li><a href="#">Trade-In Program</a></li>
                                <li><a href="#">Warranty Claims</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="lp-footer-heading">Customer Portal</h4>
                            <ul className="lp-footer-links">
                                <li><a href="#">Track a Repair</a></li>
                                <li><a href="#">Make a Payment</a></li>
                                <li><a href="#">Refer a Friend</a></li>
                                <li><a href="#">My Account</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 className="lp-footer-heading">Visit Us</h4>
                            <ul className="lp-contact-info lp-footer-links">
                                <li>
                                    <i className="bi bi-geo-alt-fill"></i>
                                    <span>Asikoro District<br />Bayelsa State, Nigeria</span>
                                </li>
                                <li>
                                    <i className="bi bi-telephone-fill"></i>
                                    <span>+234 (0) 800 000 0000</span>
                                </li>
                                <li>
                                    <i className="bi bi-envelope-fill"></i>
                                    <span>support@afprospos.com</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div className="lp-footer-bottom">&copy; 2026 AfProsPos. All rights reserved.</div>
                </footer>
            </div>
        </>
    )
}
