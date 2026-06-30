<?php
/**
 * Reusable Button component.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue shared button styles.
 */
function one1_enqueue_button_assets() {
	$ver  = '1.0.2';
	$base = get_stylesheet_directory_uri() . '/assets/homie';

	wp_enqueue_style(
		'one-button',
		$base . '/one-button.css',
		array(),
		$ver
	);
}

/**
 * Render an inline SVG icon for the button.
 *
 * @param string $icon Icon key.
 */
function one1_button_render_icon( $icon ) {
	switch ( $icon ) {
		case 'arrow-right':
			?>
			<svg class="one-btn__svg one-btn__svg--arrow-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<line x1="5" y1="12" x2="19" y2="12"></line>
				<polyline points="12 5 19 12 12 19"></polyline>
			</svg>
			<?php
			break;
		case 'arrow-up-right':
			?>
			<svg class="one-btn__svg one-btn__svg--arrow-up-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<line x1="7" y1="17" x2="17" y2="7"></line>
				<polyline points="7 7 17 7 17 17"></polyline>
			</svg>
			<?php
			break;
		case 'dual-arrow':
			?>
			<span class="one-btn__icon-wrap" aria-hidden="true">
				<svg class="one-btn__svg one-btn__svg--arrow-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="5" y1="12" x2="19" y2="12"></line>
					<polyline points="12 5 19 12 12 19"></polyline>
				</svg>
				<svg class="one-btn__svg one-btn__svg--arrow-up-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="7" y1="17" x2="17" y2="7"></line>
					<polyline points="7 7 17 7 17 17"></polyline>
				</svg>
			</span>
			<?php
			break;
		case 'menu':
			?>
			<svg class="one-btn__svg one-btn__svg--menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<line x1="3" y1="6" x2="21" y2="6"></line>
				<line x1="3" y1="12" x2="21" y2="12"></line>
				<line x1="3" y1="18" x2="21" y2="18"></line>
			</svg>
			<?php
			break;
		case 'spinner':
			?>
			<svg class="one-btn__spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
				<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="12"></circle>
			</svg>
			<?php
			break;
		default:
			if ( 0 === strpos( $icon, 'material:' ) ) {
				$symbol = substr( $icon, 9 );
				?>
				<span class="material-symbols-outlined one-btn__material" aria-hidden="true"><?php echo esc_html( $symbol ); ?></span>
				<?php
			}
			break;
	}
}

/**
 * Output a reusable button.
 *
 * @param array $args Button arguments.
 */
function one1_button( $args = array() ) {
	$defaults = array(
		'label'         => '',
		'url'           => '',
		'type'          => 'button',
		'variant'       => 'primary',
		'size'          => 'md',
		'skin'          => 'homie',
		'icon'          => 'none',
		'icon_position' => 'after',
		'description'   => '',
		'block'         => false,
		'disabled'      => false,
		'loading'       => false,
		'class'         => '',
		'name'          => '',
		'value'         => '',
		'element'       => '',
		'action_style'  => 'default',
		'tile_muted'    => false,
		'attrs'         => array(),
	);

	$args = wp_parse_args( $args, $defaults );

	$variant = sanitize_key( $args['variant'] );
	$size    = sanitize_key( $args['size'] );
	$skin    = sanitize_key( $args['skin'] );

	$classes = array(
		'one-btn',
		'one-btn--' . $variant,
		'one-btn--' . $size,
		'one-btn--skin-' . $skin,
	);

	if ( $args['block'] ) {
		$classes[] = 'one-btn--block';
	}
	if ( $args['disabled'] ) {
		$classes[] = 'one-btn--disabled';
	}
	if ( $args['loading'] ) {
		$classes[] = 'one-btn--loading';
	}
	if ( 'gold' === $args['action_style'] ) {
		$classes[] = 'one-btn--action-gold';
	}
	if ( $args['tile_muted'] ) {
		$classes[] = 'one-btn--tile-muted';
	}
	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}

	$class_attr = esc_attr( implode( ' ', array_filter( $classes ) ) );

	$extra_attrs = '';
	foreach ( (array) $args['attrs'] as $attr_key => $attr_val ) {
		if ( null === $attr_val || false === $attr_val ) {
			continue;
		}
		$extra_attrs .= sprintf( ' %s="%s"', esc_attr( $attr_key ), esc_attr( (string) $attr_val ) );
	}

	$is_link    = ! empty( $args['url'] );
	$tag        = $args['element'] ? sanitize_key( $args['element'] ) : ( $is_link ? 'a' : 'button' );
	$is_disabled = $args['disabled'] || $args['loading'];

	if ( 'a' === $tag ) {
		$href = $is_disabled ? '#' : esc_url( $args['url'] );
		printf( '<a href="%s" class="%s"', $href, $class_attr );
		if ( $is_disabled ) {
			echo ' aria-disabled="true" tabindex="-1"';
		}
		if ( $args['loading'] ) {
			echo ' aria-busy="true"';
		}
		echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '>';
	} elseif ( 'span' === $tag ) {
		printf( '<span class="%s"', $class_attr );
		if ( $args['loading'] ) {
			echo ' aria-busy="true"';
		}
		echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '>';
	} else {
		printf( '<button type="%s" class="%s"', esc_attr( $args['type'] ), $class_attr );
		if ( $is_disabled ) {
			echo ' disabled';
		}
		if ( $args['loading'] ) {
			echo ' aria-busy="true"';
		}
		if ( $args['name'] ) {
			printf( ' name="%s"', esc_attr( $args['name'] ) );
		}
		if ( $args['value'] ) {
			printf( ' value="%s"', esc_attr( $args['value'] ) );
		}
		echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '>';
	}

	if ( $args['loading'] ) {
		one1_button_render_icon( 'spinner' );
	}

	if ( 'tile' === $variant ) {
		if ( $args['label'] ) {
			printf( '<span class="one-btn__tile-title">%s</span>', esc_html( $args['label'] ) );
		}
		if ( $args['description'] ) {
			printf( '<span class="one-btn__tile-meta">%s</span>', esc_html( $args['description'] ) );
		}
	} elseif ( 'icon' === $variant ) {
		if ( 'none' !== $args['icon'] ) {
			one1_button_render_icon( $args['icon'] );
		}
	} else {
		$icon_html = '';
		if ( 'none' !== $args['icon'] && ! $args['loading'] ) {
			ob_start();
			if ( 'primary' === $variant || 'secondary' === $variant ) {
				if ( 'dual-arrow' === $args['icon'] ) {
					echo '<span class="one-btn__icon-circle">';
					one1_button_render_icon( 'dual-arrow' );
					echo '</span>';
				} elseif ( 'arrow-up-right' === $args['icon'] ) {
					echo '<span class="one-btn__icon-circle">';
					one1_button_render_icon( 'arrow-up-right' );
					echo '</span>';
				}
			} elseif ( 'outline' === $variant && 'dual-arrow' === $args['icon'] ) {
				echo '<span class="one-btn__icon-circle one-btn__icon-circle--outline">';
				one1_button_render_icon( 'dual-arrow' );
				echo '</span>';
			} elseif ( 0 === strpos( $args['icon'], 'material:' ) ) {
				one1_button_render_icon( $args['icon'] );
			} elseif ( 'arrow-up-right' === $args['icon'] ) {
				echo '<span class="one-btn__icon-circle">';
				one1_button_render_icon( 'arrow-up-right' );
				echo '</span>';
			}
			$icon_html = ob_get_clean();
		}

		$label_html = $args['label'] ? sprintf( '<span>%s</span>', esc_html( $args['label'] ) ) : '';

		if ( 'before' === $args['icon_position'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $icon_html . $label_html;
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $label_html . $icon_html;
		}
	}

	if ( 'span' === $tag ) {
		echo '</span>';
	} elseif ( 'a' === $tag ) {
		echo '</a>';
	} else {
		echo '</button>';
	}
}
