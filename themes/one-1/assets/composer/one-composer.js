/**
 * Global post composer modal.
 */
(function () {
	const cfg = typeof oneComposerConfig !== 'undefined' ? oneComposerConfig : null;
	let editingPostId = 0;

	function getComposer() {
		return document.querySelector('[data-one-composer]');
	}

	function getPostIdField() {
		const root = getComposer();
		return root ? root.querySelector('[data-one-composer-post-id]') : null;
	}

	function getTitleEl() {
		const root = getComposer();
		return root ? root.querySelector('#one-composer-title') : null;
	}

	function getSubmitBtn() {
		const root = getComposer();
		return root ? root.querySelector('[data-one-composer-submit]') : null;
	}

	function setCreateMode() {
		editingPostId = 0;
		const postIdField = getPostIdField();
		const titleEl = getTitleEl();
		const submitBtn = getSubmitBtn();
		if (postIdField) {
			postIdField.value = '';
		}
		if (titleEl && cfg) {
			titleEl.textContent = cfg.i18n.createTitle;
		}
		if (submitBtn && cfg) {
			submitBtn.textContent = cfg.i18n.publish;
		}
		const commentsToggle = getComposer()?.querySelector('[data-one-composer-comments-toggle]');
		const hideLikesToggle = getComposer()?.querySelector('[data-one-composer-hide-likes-toggle]');
		if (commentsToggle) {
			commentsToggle.checked = true;
		}
		if (hideLikesToggle) {
			hideLikesToggle.checked = false;
		}
		const notifyWrap = getComposer()?.querySelector('[data-one-composer-notify-friends-wrap]');
		if (notifyWrap) {
			notifyWrap.hidden = false;
		}
		const notifyToggle = getComposer()?.querySelector('[data-one-composer-notify-friends-toggle]');
		if (notifyToggle) {
			notifyToggle.checked = true;
		}
	}

	function setEditMode(postId) {
		editingPostId = postId;
		const postIdField = getPostIdField();
		const titleEl = getTitleEl();
		const submitBtn = getSubmitBtn();
		if (postIdField) {
			postIdField.value = String(postId);
		}
		if (titleEl && cfg) {
			titleEl.textContent = cfg.i18n.editTitle;
		}
		if (submitBtn && cfg) {
			submitBtn.textContent = cfg.i18n.saveChanges;
		}
		const notifyWrap = getComposer()?.querySelector('[data-one-composer-notify-friends-wrap]');
		if (notifyWrap) {
			notifyWrap.hidden = true;
		}
	}

	function showExistingImage(url) {
		const root = getComposer();
		if (!root || !url) {
			return;
		}
		const drop = root.querySelector('[data-one-story-file-drop]');
		const previewWrap = root.querySelector('[data-one-composer-media] [data-one-story-file-preview]');
		if (!drop || !previewWrap) {
			return;
		}
		previewWrap.innerHTML =
			'<img src="' +
			url +
			'" alt="" /><button type="button" class="one-composer__media-remove" data-one-composer-clear-media>Remove photo</button>';
		previewWrap.removeAttribute('hidden');
		drop.setAttribute('hidden', 'hidden');
		previewWrap.querySelector('[data-one-composer-clear-media]')?.addEventListener('click', () => {
			const input = root.querySelector('[data-one-story-file-input]');
			if (input) {
				input.value = '';
			}
			previewWrap.setAttribute('hidden', 'hidden');
			previewWrap.innerHTML = '';
			drop.removeAttribute('hidden');
		});
	}

	function populateComposerForm(data) {
		const root = getComposer();
		if (!root || !data) {
			return;
		}

		const textarea = root.querySelector('.one-composer__textarea');
		const titleInput = root.querySelector('#one-composer-title-input');
		const donationToggle = root.querySelector('[data-one-story-donation-toggle]');
		const goalInput = root.querySelector('[data-one-goal-input]');
		const endInput = root.querySelector('[data-one-composer-end-date]');
		const locationInput = root.querySelector('#one-composer-location-input');
		const placeIdField = root.querySelector('[data-one-location-place-id]');
		const cityField = root.querySelector('[data-one-location-city]');
		const regionField = root.querySelector('[data-one-location-region]');
		const locationPanel = root.querySelector('[data-one-composer-location-panel]');
		const locationToggle = root.querySelector('[data-one-composer-location-toggle]');

		if (textarea) {
			textarea.value = data.content || '';
		}
		if (titleInput) {
			titleInput.value = data.title || '';
		}
		if (donationToggle) {
			donationToggle.checked = !!data.is_donation;
			donationToggle.dispatchEvent(new Event('change'));
		}
		if (goalInput) {
			goalInput.value = data.fundraising_goal || '';
			goalInput.dispatchEvent(new Event('input'));
		}
		if (endInput) {
			endInput.value = data.end_date || '';
		}
		if (locationInput) {
			locationInput.value = data.location_label || '';
			locationInput.dispatchEvent(new Event('input'));
		}
		if (placeIdField) {
			placeIdField.value = data.location_place_id || '';
		}
		if (cityField) {
			cityField.value = data.city || '';
		}
		if (regionField) {
			regionField.value = data.state_region || '';
		}
		if (data.urgency) {
			const urgencyInput = root.querySelector('input[name="one_story_urgency"][value="' + data.urgency + '"]');
			if (urgencyInput) {
				urgencyInput.checked = true;
				urgencyInput.dispatchEvent(new Event('change'));
			}
		}
		if (locationPanel && locationToggle && data.location_label) {
			locationPanel.removeAttribute('hidden');
			locationToggle.setAttribute('aria-expanded', 'true');
		}
		if (data.thumbnail_url) {
			showExistingImage(data.thumbnail_url);
		}

		const commentsToggle = root.querySelector('[data-one-composer-comments-toggle]');
		const hideLikesToggle = root.querySelector('[data-one-composer-hide-likes-toggle]');
		if (commentsToggle) {
			commentsToggle.checked = data.comments_enabled !== false;
		}
		if (hideLikesToggle) {
			hideLikesToggle.checked = !!data.hide_likes;
		}
	}

	function initComposerMedia() {
		const root = getComposer();
		if (!root) {
			return;
		}
		const drop = root.querySelector('[data-one-story-file-drop]');
		const input = root.querySelector('[data-one-story-file-input]');
		const previewWrap = root.querySelector('[data-one-composer-media] [data-one-story-file-preview]');
		if (!drop || !input || !previewWrap) {
			return;
		}

		const clearMedia = () => {
			input.value = '';
			previewWrap.setAttribute('hidden', 'hidden');
			previewWrap.innerHTML = '';
			drop.removeAttribute('hidden');
		};

		const showFile = (file) => {
			if (!file || !file.type.startsWith('image/')) {
				return;
			}
			const url = URL.createObjectURL(file);
			previewWrap.innerHTML =
				'<img src="' +
				url +
				'" alt="" /><button type="button" class="one-composer__media-remove" data-one-composer-clear-media>' +
				'Remove photo</button>';
			previewWrap.removeAttribute('hidden');
			drop.setAttribute('hidden', 'hidden');
			previewWrap.querySelector('[data-one-composer-clear-media]')?.addEventListener('click', clearMedia);
		};

		input.addEventListener('change', () => {
			if (input.files && input.files[0]) {
				showFile(input.files[0]);
			}
		});

		['dragenter', 'dragover'].forEach((ev) => {
			drop.addEventListener(ev, (e) => {
				e.preventDefault();
				drop.classList.add('is-dragover');
			});
		});
		['dragleave', 'drop'].forEach((ev) => {
			drop.addEventListener(ev, (e) => {
				e.preventDefault();
				drop.classList.remove('is-dragover');
			});
		});
		drop.addEventListener('drop', (e) => {
			const files = e.dataTransfer && e.dataTransfer.files;
			if (files && files.length) {
				input.files = files;
				showFile(files[0]);
			}
		});
	}

	function initComposerDonation() {
		const root = getComposer();
		if (!root) {
			return;
		}

		const toggle = root.querySelector('[data-one-story-donation-toggle]');
		const wrap = root.querySelector('[data-one-story-donation-wrap]');
		const panel = root.querySelector('[data-one-story-donation-panel]');
		if (!toggle || !wrap || !panel) {
			return;
		}

		const required = panel.querySelectorAll('[data-one-donation-required]');

		const syncDonation = () => {
			const on = toggle.checked;
			if (on) {
				wrap.removeAttribute('hidden');
				panel.removeAttribute('hidden');
			} else {
				wrap.setAttribute('hidden', 'hidden');
				panel.setAttribute('hidden', 'hidden');
			}
			required.forEach((el) => {
				if (on) {
					el.setAttribute('required', 'required');
				} else {
					el.removeAttribute('required');
				}
			});
		};

		toggle.addEventListener('change', syncDonation);
		syncDonation();

		const goalInput = root.querySelector('[data-one-goal-input]');
		const presets = root.querySelectorAll('[data-one-goal-preset]');

		const syncPresets = () => {
			const val = goalInput ? String(goalInput.value).replace(/[^\d.]/g, '') : '';
			presets.forEach((btn) => {
				btn.classList.toggle('is-active', val !== '' && btn.getAttribute('data-one-goal-preset') === val);
			});
		};

		presets.forEach((btn) => {
			btn.addEventListener('click', () => {
				if (!goalInput) {
					return;
				}
				goalInput.value = btn.getAttribute('data-one-goal-preset') || '';
				syncPresets();
				goalInput.focus();
			});
		});

		if (goalInput) {
			goalInput.addEventListener('input', syncPresets);
			syncPresets();
		}

		const urgencyGroup = root.querySelector('[data-one-composer-urgency]');
		if (urgencyGroup) {
			urgencyGroup.querySelectorAll('.one-composer__segment input').forEach((input) => {
				input.addEventListener('change', () => {
					urgencyGroup.querySelectorAll('.one-composer__segment').forEach((seg) => {
						seg.classList.toggle('is-active', seg.querySelector('input') === input && input.checked);
					});
				});
			});
		}
	}

	function openComposer() {
		const root = getComposer();
		if (!root) {
			return;
		}
		setCreateMode();
		root.removeAttribute('hidden');
		root.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-composer-open');
		const textarea = root.querySelector('.one-composer__textarea');
		if (textarea) {
			setTimeout(() => textarea.focus(), 80);
		}
	}

	async function openComposerForEdit(postId) {
		const root = getComposer();
		if (!root || !cfg || !cfg.ajaxUrl || !cfg.editNonce) {
			return;
		}

		const body = new FormData();
		body.append('action', 'one_story_get_edit_data');
		body.append('nonce', cfg.editNonce);
		body.append('post_id', String(postId));

		try {
			const res = await fetch(cfg.ajaxUrl, {
				method: 'POST',
				body,
				credentials: 'same-origin',
			});
			const json = await res.json();
			if (!json.success || !json.data) {
				throw new Error((json.data && json.data.message) || cfg.i18n.error);
			}

			const form = root.querySelector('[data-one-composer-form]');
			const err = root.querySelector('[data-one-composer-error]');
			if (form) {
				form.reset();
			}
			if (err) {
				err.setAttribute('hidden', 'hidden');
				err.textContent = '';
			}
			initComposerDonation();
			initComposerMedia();

			setEditMode(postId);
			populateComposerForm(json.data);

			root.removeAttribute('hidden');
			root.setAttribute('aria-hidden', 'false');
			document.body.classList.add('one-composer-open');
			const textarea = root.querySelector('.one-composer__textarea');
			if (textarea) {
				setTimeout(() => textarea.focus(), 80);
			}
		} catch (err) {
			window.alert((err && err.message) || cfg.i18n.error);
		}
	}

	function closeComposer() {
		const root = getComposer();
		if (!root) {
			return;
		}
		root.setAttribute('hidden', 'hidden');
		root.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-composer-open');
		const form = root.querySelector('[data-one-composer-form]');
		const err = root.querySelector('[data-one-composer-error]');
		if (form) {
			form.reset();
		}
		if (err) {
			err.setAttribute('hidden', 'hidden');
			err.textContent = '';
		}
		setCreateMode();
		initComposerDonation();
		initComposerMedia();
	}

	function bindComposer() {
		const root = getComposer();
		if (!root) {
			return;
		}

		root.querySelectorAll('[data-one-composer-close]').forEach((el) => {
			el.addEventListener('click', closeComposer);
		});

		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && root.getAttribute('aria-hidden') === 'false') {
				closeComposer();
			}
		});

		document.querySelectorAll('[data-one-open-composer]').forEach((trigger) => {
			trigger.addEventListener('click', (e) => {
				if (trigger.tagName === 'A') {
					e.preventDefault();
				}
				openComposer();
			});
		});

		const form = root.querySelector('[data-one-composer-form]');
		if (!form || !cfg) {
			return;
		}

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = root.querySelector('[data-one-composer-submit]');
			const errEl = root.querySelector('[data-one-composer-error]');
			const isEdit = editingPostId > 0;
			const defaultLabel = isEdit ? cfg.i18n.saveChanges : cfg.i18n.publish;
			const loadingLabel = isEdit ? cfg.i18n.saving : cfg.i18n.publishing;

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = loadingLabel;
			}
			if (errEl) {
				errEl.setAttribute('hidden', 'hidden');
			}

			const body = new FormData(form);
			body.append('action', isEdit ? 'one_story_update' : 'one_story_create');

			try {
				const res = await fetch(cfg.ajaxUrl, {
					method: 'POST',
					body,
					credentials: 'same-origin',
				});
				const json = await res.json();
				if (!json.success) {
					const msg = (json.data && json.data.message) || cfg.i18n.error;
					if (errEl) {
						errEl.textContent = msg;
						errEl.removeAttribute('hidden');
					}
					return;
				}
				const savedPostId = json.data.post_id;
				closeComposer();
				if (isEdit) {
					document.dispatchEvent(
						new CustomEvent('one:story-updated', {
							detail: {
								postId: savedPostId,
								thumbnailUrl: json.data.thumbnail_url || '',
							},
						})
					);
					if (window.location.pathname.indexOf('/stories/') !== -1) {
						window.location.reload();
					}
				} else {
					document.dispatchEvent(
						new CustomEvent('one:story-created', {
							detail: { postId: savedPostId },
						})
					);
					if (
						window.location.pathname.indexOf('/share') !== -1 ||
						window.location.pathname.indexOf('/profile') !== -1
					) {
						window.location.reload();
					}
				}
			} catch (err) {
				if (errEl) {
					errEl.textContent = cfg.i18n.error;
					errEl.removeAttribute('hidden');
				}
			} finally {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = defaultLabel;
				}
			}
		});
	}

	function initComposerLocation() {
		const root = getComposer();
		if (!root) {
			return;
		}
		const toggle = root.querySelector('[data-one-composer-location-toggle]');
		const panel = root.querySelector('[data-one-composer-location-panel]');
		const input = root.querySelector('#one-composer-location-input');
		const clearBtn = root.querySelector('[data-one-composer-location-clear]');
		const placeIdField = root.querySelector('[data-one-location-place-id]');
		const cityField = root.querySelector('[data-one-location-city]');
		const regionField = root.querySelector('[data-one-location-region]');

		const syncClear = () => {
			if (!clearBtn || !input) {
				return;
			}
			if (input.value.trim()) {
				clearBtn.removeAttribute('hidden');
			} else {
				clearBtn.setAttribute('hidden', 'hidden');
			}
		};

		const syncPacContainerLayout = () => {
			const pac = document.querySelector('.pac-container');
			if (!pac || !input || pac.style.display === 'none') {
				return;
			}
			const rect = input.getBoundingClientRect();
			pac.style.width = `${Math.round(rect.width)}px`;
			pac.style.left = `${Math.round(rect.left)}px`;
		};

		if (toggle && panel) {
			toggle.addEventListener('click', () => {
				const open = panel.hasAttribute('hidden');
				if (open) {
					panel.removeAttribute('hidden');
					toggle.setAttribute('aria-expanded', 'true');
					input?.focus();
					window.setTimeout(syncPacContainerLayout, 0);
				} else {
					panel.setAttribute('hidden', 'hidden');
					toggle.setAttribute('aria-expanded', 'false');
				}
			});
			if (input && input.value.trim()) {
				panel.removeAttribute('hidden');
				toggle.setAttribute('aria-expanded', 'true');
			}
		}

		if (clearBtn && input) {
			clearBtn.addEventListener('click', () => {
				input.value = '';
				if (placeIdField) {
					placeIdField.value = '';
				}
				if (cityField) {
					cityField.value = '';
				}
				if (regionField) {
					regionField.value = '';
				}
				syncClear();
			});
			input.addEventListener('input', syncClear);
			syncClear();
		}

		if (input) {
			input.addEventListener('focus', syncPacContainerLayout);
			input.addEventListener('input', syncPacContainerLayout);
			window.addEventListener('resize', syncPacContainerLayout);
		}

		if (input && cfg && cfg.placesKey && window.google && google.maps && google.maps.places) {
			const autocomplete = new google.maps.places.Autocomplete(input, {
				types: ['(regions)'],
				fields: ['place_id', 'formatted_address', 'address_components', 'name'],
			});
			autocomplete.addListener('place_changed', () => {
				const place = autocomplete.getPlace();
				if (!place) {
					return;
				}
				const label = place.formatted_address || place.name || input.value;
				input.value = label;
				if (placeIdField && place.place_id) {
					placeIdField.value = place.place_id;
				}
				let city = '';
				let region = '';
				(place.address_components || []).forEach((comp) => {
					if (comp.types.includes('locality')) {
						city = comp.long_name;
					}
					if (comp.types.includes('administrative_area_level_1')) {
						region = comp.long_name;
					}
					if (!city && comp.types.includes('postal_town')) {
						city = comp.long_name;
					}
				});
				if (cityField) {
					cityField.value = city;
				}
				if (regionField) {
					regionField.value = region;
				}
				if (panel) {
					panel.removeAttribute('hidden');
				}
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'true');
				}
				syncClear();
			});
		}
	}

	function initComposerDatePicker() {
		const root = getComposer();
		if (!root) {
			return;
		}
		root.querySelectorAll('[data-one-composer-date-field]').forEach((wrap) => {
			const input = wrap.querySelector('[data-one-composer-end-date]');
			if (!input || wrap.dataset.oneDateBound === 'true') {
				return;
			}
			wrap.dataset.oneDateBound = 'true';
			const openPicker = () => {
				if (typeof input.showPicker === 'function') {
					try {
						input.showPicker();
						return;
					} catch (err) {
						/* fall through */
					}
				}
				input.focus();
				input.click();
			};
			wrap.addEventListener('click', (e) => {
				if (e.target === input) {
					return;
				}
				openPicker();
			});
			input.addEventListener('click', () => {
				if (typeof input.showPicker === 'function') {
					try {
						input.showPicker();
					} catch (err) {
						/* ignore */
					}
				}
			});
		});
	}

	window.OneComposer = {
		open: openComposer,
		openForEdit: openComposerForEdit,
		close: closeComposer,
	};

	function init() {
		initComposerDonation();
		initComposerMedia();
		initComposerLocation();
		initComposerDatePicker();
		bindComposer();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
