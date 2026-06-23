<?php
/**
 * Post detail modal shell (content loaded via AJAX).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="one-post-modal" data-one-post-modal hidden aria-hidden="true">
	<div class="one-post-modal__backdrop" data-one-post-modal-close tabindex="-1"></div>
	<div class="one-post-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="one-post-modal-title">
		<header class="one-post-modal__header">
			<h2 id="one-post-modal-title" class="one-post-modal__title"><?php esc_html_e( 'Post', 'one' ); ?></h2>
			<button type="button" class="one-post-modal__close" data-one-post-modal-close aria-label="<?php esc_attr_e( 'Close', 'one' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</header>
		<div class="one-post-modal__body" data-one-post-modal-body>
			<p class="one-post-modal__loading"><?php esc_html_e( 'Loading…', 'one' ); ?></p>
		</div>
	</div>
</div>
