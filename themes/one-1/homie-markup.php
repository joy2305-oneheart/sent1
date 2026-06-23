<?php
/**
 * Sent One landing markup (included from homie-home.php).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$one_homie_img     = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/homie/images/';
$one_homie_video   = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/homie/video/';
$one_share_url     = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
$one_logged_in = is_user_logged_in();
?>
<div class="homie-homepage">
<header id="header" class="homie-site-header">
        <div class="homie-header-outer">
            <div class="homie-header-inner">
            <div class="header-inner">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="handleLogoClick(event)" class="logo-link">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
                    </svg>
                    <span class="logo-text">Sent One</span>
                </a>

                <nav class="desktop-nav">
                    <a href="#how-it-works" onclick="handleSmoothScroll(event, 'how-it-works')" class="nav-link"><?php esc_html_e( 'How it works', 'one' ); ?></a>
                    <a href="#testimonials" onclick="handleSmoothScroll(event, 'testimonials')" class="nav-link"><?php esc_html_e( 'Voices', 'one' ); ?></a>
                    <a href="#faq" onclick="handleSmoothScroll(event, 'faq')" class="nav-link">FAQ</a>
                </nav>

                <div class="header-toolbar">
                    <div class="desktop-actions">
						<?php one1_render_homie_header_actions(); ?>
                    </div>
					<?php if ( $one_logged_in ) : ?>
						<?php one1_render_user_menu( 'homie' ); ?>
					<?php endif; ?>
					<?php
					one1_button(
						array(
							'variant' => 'icon',
							'icon'    => 'menu',
							'class'   => 'one-btn--menu-toggle',
							'attrs'   => array(
								'id'             => 'mobile-menu-btn',
								'aria-expanded'  => 'false',
								'aria-controls'  => 'mobile-nav',
							),
						)
					);
					?>
                </div>
            </div>

            <nav id="mobile-nav" class="mobile-nav" style="display: none;">
                <a href="#how-it-works" onclick="handleSmoothScroll(event, 'how-it-works')" class="mobile-nav-link"><?php esc_html_e( 'How it works', 'one' ); ?></a>
                <a href="#testimonials" onclick="handleSmoothScroll(event, 'testimonials')" class="mobile-nav-link"><?php esc_html_e( 'Voices', 'one' ); ?></a>
                <a href="#faq" onclick="handleSmoothScroll(event, 'faq')" class="mobile-nav-link">FAQ</a>
				<?php one1_render_homie_mobile_auth(); ?>
            </nav>
            </div>
        </div>
    </header>

    <main class="min-h-screen">
        <!-- HERO / BANNER -->
        <section class="hero-section homie-hero-banner" aria-label="<?php esc_attr_e( 'Home banner', 'one' ); ?>">
            <div class="homie-hero-banner__frame">
                <div class="hero-video-container">
                    <video autoplay loop muted playsinline class="hero-video">
                        <source src="<?php echo esc_url( $one_homie_video . 'charity.mp4' ); ?>" type="video/mp4">
                    </video>
                </div>

                <div class="hero-background-text" aria-hidden="true"><?php esc_html_e( 'SENT ONE', 'one' ); ?></div>
            </div>
        </section>

        <!-- PLATFORM SECTION -->
        <section id="how-it-works" class="services-section">
            <div class="services-background-text" aria-hidden="true"><?php esc_html_e( 'CONNECT', 'one' ); ?></div>

            <div class="max-w-7xl mx-auto ">
                <div class="services-hero-box">
                    <img src="<?php echo esc_url( $one_homie_img . 'charity.jpg' ); ?>" alt="<?php esc_attr_e( 'People sharing a journey on Sent One', 'one' ); ?>" class="services-hero-img">
                    <div class="services-hero-overlay"></div>

                    <div class="services-hero-content">
                        <div class="services-hero-text">
                            <p class="services-label"><?php esc_html_e( 'Our mission', 'one' ); ?></p>
                            <h2 class="services-title"><?php esc_html_e( 'Stories that keep people close', 'one' ); ?></h2>
                            <div class="services-description">
                                <p><?php esc_html_e( 'Sent One is a place to share your journey with the people who matter most. Post updates, photos, and milestones so loved ones stay connected—whether you are healing, traveling, growing a family, or walking through something hard.', 'one' ); ?></p>
                                <p><?php esc_html_e( 'When it fits your path, you can also publish donation posts so friends and community can support a cause that is part of your story. One space for honesty, care, and staying in step together.', 'one' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="services-cards-header">
                    <h2 class="services-cards-title"><?php esc_html_e( 'Built for real life', 'one' ); ?></h2>
                    <p class="services-cards-description"><?php esc_html_e( 'Share updates, invite your circle, and optionally rally support—without losing the human side of what you are going through.', 'one' ); ?></p>
                </div>

                <div class="services-cards">
                    <div class="service-card">
                        <div class="service-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <h3 class="service-title"><?php esc_html_e( 'Your story, your words', 'one' ); ?></h3>
                        <p class="service-description"><?php esc_html_e( 'Write long-form updates, add moments in time, and keep everything in one gentle timeline people can return to.', 'one' ); ?></p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="service-title"><?php esc_html_e( 'Circle who walks with you', 'one' ); ?></h3>
                        <p class="service-description"><?php esc_html_e( 'Invite family and friends who are part of your journey so they get the same updates—no group chats lost in the scroll.', 'one' ); ?></p>
                    </div>
                    <div class="service-card">
                        <div class="service-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                        <h3 class="service-title"><?php esc_html_e( 'Support when you choose', 'one' ); ?></h3>
                        <p class="service-description"><?php esc_html_e( 'Optional donation posts let your community contribute to medical bills, recovery, travel, or a cause you name—with clarity and consent.', 'one' ); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA SECTION -->
        <section class="cta-section">
            <div class="cta-background-text">TOGETHER</div>

            <div class="max-w-7xl mx-auto">
                <div class="cta-content">
                    <h2 class="cta-title"><?php esc_html_e( 'Ready to open your journey?', 'one' ); ?></h2>
                    <p class="cta-description"><?php esc_html_e( 'Start a page for your story, invite the people who should walk beside you, and share updates on your terms—including support for a cause when the moment is right.', 'one' ); ?></p>

                    <div class="cta-buttons">
						<?php if ( $one_logged_in ) : ?>
						<?php
						one1_button(
							array(
								'url'     => $one_share_url,
								'label'   => __( 'Start sharing', 'one' ),
								'variant' => 'primary',
								'size'    => 'lg',
								'skin'    => 'homie',
								'icon'    => 'arrow-up-right',
							)
						);
						?>
						<?php else : ?>
						<?php
						one1_button(
							array(
								'url'     => one1_login_url(),
								'label'   => __( 'Log in', 'one' ),
								'variant' => 'primary',
								'size'    => 'lg',
								'skin'    => 'homie',
								'icon'    => 'arrow-up-right',
							)
						);
						one1_button(
							array(
								'url'     => one1_login_url(),
								'label'   => __( 'Follow someone’s story', 'one' ),
								'variant' => 'secondary',
								'size'    => 'lg',
								'skin'    => 'homie',
								'icon'    => 'dual-arrow',
							)
						);
						?>
						<?php endif; ?>
                    </div>

                    <div class="cta-stats">
                        <div class="cta-stat">
                            <p class="cta-stat-value">12K+</p>
                            <p class="cta-stat-label"><?php esc_html_e( 'Journeys shared', 'one' ); ?></p>
                        </div>
                        <div class="cta-stat">
                            <p class="cta-stat-value">180K+</p>
                            <p class="cta-stat-label"><?php esc_html_e( 'Updates sent to circles', 'one' ); ?></p>
                        </div>
                        <div class="cta-stat">
                            <p class="cta-stat-value">$4M+</p>
                            <p class="cta-stat-label"><?php esc_html_e( 'Raised for personal causes', 'one' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- TESTIMONIALS SECTION -->
        <section id="testimonials" class="testimonials-section">
            <div class="max-w-7xl mx-auto">
                <div class="testimonials-header">
                    <h2 class="testimonials-title"><?php esc_html_e( 'From people on the path', 'one' ); ?></h2>
                </div>
            </div>

            <div class="testimonials-marquees">
                <div class="testimonials-marquee-viewport">
                    <div class="testimonials-row" id="testimonials-row-1"></div>
                </div>
                <div class="testimonials-marquee-viewport">
                    <div class="testimonials-row" id="testimonials-row-2"></div>
                </div>
            </div>
        </section>

        <!-- FAQ SECTION -->
        <section id="faq" class="faq-section">
            <div class="max-w-4xl mx-auto">
                <div class="faq-header">
                    <h2 class="faq-title"><?php esc_html_e( 'Frequently asked questions', 'one' ); ?></h2>
                    <p class="faq-description"><?php esc_html_e( 'How Sent One works for storytellers, families, and supporters. If you do not see your question, reach out—we read every message.', 'one' ); ?></p>
                </div>

                <div class="faq-list" id="faq-list">
                    <!-- FAQ items will be generated by JS -->
                </div>
            </div>
        </section>
    </main>

    <footer class="footer footer--minimal">
        <div class="footer-background-text" aria-hidden="true"><?php esc_html_e( 'SENT ONE', 'one' ); ?></div>
        <div class="footer-bar">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-bar__brand">Sent One</a>
            <nav class="footer-bar__nav" aria-label="<?php esc_attr_e( 'Footer', 'one' ); ?>">
                <a href="#how-it-works" onclick="handleSmoothScroll(event, 'how-it-works')"><?php esc_html_e( 'How it works', 'one' ); ?></a>
                <a href="#testimonials" onclick="handleSmoothScroll(event, 'testimonials')"><?php esc_html_e( 'Voices', 'one' ); ?></a>
                <a href="#faq" onclick="handleSmoothScroll(event, 'faq')">FAQ</a>
            </nav>
            <p class="footer-bar__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Sent One</p>
        </div>
    </footer>

</div>
