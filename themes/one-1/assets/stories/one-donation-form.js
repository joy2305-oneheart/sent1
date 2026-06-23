/**
 * Populate Full Stripe custom fields with story/user IDs and re-init forms in modals.
 */
(function ($) {
	'use strict';

	function findInputByLabel(form, labelNeedle) {
		const needle = labelNeedle.toLowerCase();
		const labels = form.querySelectorAll('label');
		for (let i = 0; i < labels.length; i++) {
			const text = (labels[i].textContent || '').trim().toLowerCase();
			if (text === needle || text.indexOf(needle) !== -1) {
				const forId = labels[i].getAttribute('for');
				if (forId) {
					const byId = form.querySelector('#' + CSS.escape(forId));
					if (byId) {
						return byId;
					}
				}
				const group = labels[i].closest('.wpfs-form-group');
				if (group) {
					const input = group.querySelector('input, textarea, select');
					if (input) {
						return input;
					}
				}
			}
		}
		return null;
	}

	function setFieldValue(input, value) {
		if (!input || value === '' || value === null || typeof value === 'undefined') {
			return;
		}
		input.value = String(value);
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function applyStoryDonationStats(stats, storyId) {
		if (!stats || !storyId) {
			return;
		}

		const selector =
			'[data-one-donation-summary][data-story-id="' + String(storyId) + '"]';
		document.querySelectorAll(selector).forEach(function (summary) {
			const raisedEl = summary.querySelector('.sent-share-donation__raised');
			const donorsEl = summary.querySelector('.sent-share-donation__donors');
			const progressEl = summary.querySelector('.sent-share-donation__progress-bar');
			const progressWrap = summary.querySelector('.sent-share-donation__progress');

			if (raisedEl && stats.raised_label) {
				raisedEl.textContent = stats.raised_label;
			}
			if (donorsEl && stats.donors_label) {
				donorsEl.textContent = stats.donors_label;
			}
			if (progressEl && typeof stats.progress_pct === 'number') {
				progressEl.style.width = String(stats.progress_pct) + '%';
			}
			if (progressWrap) {
				progressWrap.setAttribute('aria-valuenow', String(stats.progress_pct || 0));
			}
		});
	}

	function fetchStoryDonationStats(storyId, done) {
		if (
			typeof oneDonationSync === 'undefined' ||
			!oneDonationSync.ajaxUrl ||
			!storyId
		) {
			return;
		}

		$.getJSON(oneDonationSync.ajaxUrl, {
			action: oneDonationSync.action || 'one1_story_donation_stats',
			post_id: storyId,
		})
			.done(function (resp) {
				if (resp && resp.success && resp.data) {
					applyStoryDonationStats(resp.data, storyId);
					if (typeof done === 'function') {
						done(resp.data);
					}
				}
			})
			.fail(function () {
				/* ignore */
			});
	}

	function watchDonationChargeSuccess() {
		$(document).ajaxSuccess(function (_event, xhr, settings) {
			if (!settings || typeof settings.data !== 'string') {
				return;
			}
			const data = settings.data;
			if (
				data.indexOf('wp_full_stripe_onetime_donation_charge') === -1 &&
				data.indexOf('wp_full_stripe_inline_donation_charge') === -1 &&
				data.indexOf('wp_full_stripe_inline_payment_charge') === -1
			) {
				return;
			}

			let response = xhr.responseJSON;
			if (!response && xhr.responseText) {
				try {
					response = JSON.parse(xhr.responseText);
				} catch (e) {
					response = null;
				}
			}
			if (!response || !response.success) {
				return;
			}

			const wrap = document.querySelector('.one-story-donation-form');
			const storyId =
				(wrap && wrap.getAttribute('data-story-id')) ||
				(response.oneStoryDonationStats && String(response.oneStoryDonationStats.post_id)) ||
				'';

			if (response.oneStoryDonationStats) {
				applyStoryDonationStats(response.oneStoryDonationStats, storyId);
			} else if (storyId) {
				setTimeout(function () {
					fetchStoryDonationStats(storyId);
				}, 400);
			}
		});
	}

	function hidePaymentDetails(scope) {
		const root = scope || document;
		root.querySelectorAll('.one-story-donation-form a[id^="payment-details--"]').forEach(function (el) {
			el.style.display = 'none';
		});
		root.querySelectorAll('.one-story-donation-form .wpfs-tooltip-content').forEach(function (el) {
			el.style.display = 'none';
		});
	}

	function initDonationForm(root) {
		const scope = root || document;
		scope.querySelectorAll('[data-one-donation-open]').forEach(function (btn) {
			if (btn.getAttribute('data-one-donation-bound') === '1') {
				return;
			}
			btn.setAttribute('data-one-donation-bound', '1');
			btn.addEventListener('click', function () {
				const summary = btn.closest('[data-one-donation-summary]');
				const wrap = summary
					? summary.querySelector('[data-one-donation-form-wrap]')
					: null;
				if (!wrap) {
					return;
				}
				wrap.hidden = false;
				wrap.classList.add('is-open');
				btn.hidden = true;
				initDonationForm(wrap);
				const firstInput = wrap.querySelector('input, select, textarea, button');
				if (firstInput) {
					firstInput.focus();
				}
			});
		});

		scope.querySelectorAll('.one-story-donation-form').forEach(function (wrap) {
			if (wrap.getAttribute('data-one-donation-init') === '1') {
				return;
			}
			wrap.setAttribute('data-one-donation-init', '1');

			const storyId = wrap.getAttribute('data-story-id') || '';
			const userId =
				wrap.getAttribute('data-user-id') ||
				(typeof oneDonationForm !== 'undefined' && oneDonationForm.userId
					? String(oneDonationForm.userId)
					: '');

			const form = wrap.querySelector('form') || wrap;
			setFieldValue(findInputByLabel(form, 'story_id'), storyId);
			setFieldValue(findInputByLabel(form, 'user_id'), userId);

			form.querySelectorAll('input[name*="story"], input[id*="story"]').forEach(function (input) {
				if (!input.value) {
					setFieldValue(input, storyId);
				}
			});
			form.querySelectorAll('input[name*="user_id"], input[id*="user_id"]').forEach(function (input) {
				if (!input.value) {
					setFieldValue(input, userId);
				}
			});
		});

		if (typeof window.WPFS !== 'undefined' && typeof window.WPFS.initInlineForms === 'function') {
			window.WPFS.initInlineForms();
		}

		hidePaymentDetails(scope);
	}

	function onReady() {
		initDonationForm(document);
		watchDonationChargeSuccess();
	}

	document.addEventListener('one:story-view-loaded', function (e) {
		const root = e.detail && e.detail.root ? e.detail.root : document;
		initDonationForm(root);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onReady);
	} else {
		onReady();
	}
})(jQuery);
