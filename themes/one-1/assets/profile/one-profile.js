/**
 * Profile post detail modal and profile editing.
 */
(function () {
	const cfg = typeof oneProfileConfig !== 'undefined' ? oneProfileConfig : null;
	const editCfg = typeof oneProfileEdit !== 'undefined' ? oneProfileEdit : null;

	function buildFragmentUrl(postId) {
		const url = new URL(cfg.fragmentBase || window.location.origin + '/');
		url.searchParams.set('one1_story_fragment', String(postId));
		url.searchParams.set('nonce', cfg.nonce);
		return url.toString();
	}

	function loadViaFragment(postId) {
		return fetch(buildFragmentUrl(postId), { credentials: 'same-origin' }).then((res) =>
			res.text().then((text) => {
				if (!res.ok) {
					throw new Error(text.trim() || 'Unable to load post.');
				}
				if (!text.trim()) {
					throw new Error('Unable to load post.');
				}
				return text;
			})
		);
	}

	function loadViaAjax(postId) {
		const form = new FormData();
		form.append('action', 'one_story_detail');
		form.append('nonce', cfg.nonce);
		form.append('post_id', String(postId));

		return fetch(cfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' }).then((res) =>
			res.text().then((text) => {
				const trimmed = (text || '').trim();
				if (trimmed === '0' || trimmed === '-1') {
					throw new Error('Could not load this post. Please refresh the page and try again.');
				}
				let json;
				try {
					json = JSON.parse(trimmed);
				} catch (err) {
					throw new Error('Unable to load post.');
				}
				if (json.success && json.data && json.data.html) {
					return json.data.html;
				}
				throw new Error((json.data && json.data.message) || 'Unable to load post.');
			})
		);
	}

	function loadPostHtml(postId) {
		return loadViaFragment(postId).catch(() => loadViaAjax(postId));
	}

	function openPostModal(postId) {
		const root = document.querySelector('[data-one-post-modal]');
		const body = root && root.querySelector('[data-one-post-modal-body]');
		if (!root || !body || !cfg || !cfg.nonce) {
			return;
		}

		document.querySelectorAll('[data-one-open-post]').forEach((btn) => {
			btn.classList.toggle('is-loading', btn.getAttribute('data-one-open-post') === String(postId));
		});

		root.removeAttribute('hidden');
		root.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-post-modal-open');
		body.innerHTML = '<p class="one-post-modal__loading">Loading…</p>';

		loadPostHtml(postId)
			.then((html) => {
				body.innerHTML = html;
				document.dispatchEvent(
					new CustomEvent('one:story-view-loaded', {
						detail: { root: body },
					})
				);
			})
			.catch((err) => {
				body.innerHTML =
					'<p class="one-post-modal__error">' +
					(err && err.message ? err.message : 'Unable to load post.') +
					'</p>';
			})
			.finally(() => {
				document.querySelectorAll('[data-one-open-post].is-loading').forEach((btn) => {
					btn.classList.remove('is-loading');
				});
			});
	}

	function closePostModal() {
		const root = document.querySelector('[data-one-post-modal]');
		if (!root) {
			return;
		}
		root.setAttribute('hidden', 'hidden');
		root.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-post-modal-open');
	}

	function updateAllAvatars(url) {
		if (!url) {
			return;
		}
		document
			.querySelectorAll(
				'.one-profile-card__avatar, [data-one-profile-avatar], .sent-share-nav-panel__avatar, .one-user-menu__avatar, .one-composer__avatar'
			)
			.forEach(function (img) {
				img.removeAttribute('srcset');
				img.removeAttribute('sizes');
				img.src = url;
			});
	}

	function initProfileEdit() {
		const card = document.querySelector('[data-one-profile-card]');
		if (!card || !editCfg) {
			return;
		}

		const toggleBtn = card.querySelector('[data-one-profile-edit-toggle]');
		const saveBtns = card.querySelectorAll('[data-one-profile-edit-save]');
		const editForm = card.querySelector('[data-one-profile-edit-form]');
		const bioDisplay = card.querySelector('[data-one-profile-bio-display]');
		const bioInput = card.querySelector('[data-one-profile-bio-input]');
		const bioCount = card.querySelector('[data-one-profile-bio-count]');
		const avatarInput = card.querySelector('[data-one-profile-avatar-input]');
		const avatarImg = card.querySelector('[data-one-profile-avatar], .one-profile-card__avatar');
		const editOnly = card.querySelectorAll('[data-one-profile-edit-only]');
		const menuDefaultItems = card.querySelectorAll('[data-one-profile-menu-default]');
		const menuPanel = card.querySelector('[data-one-profile-menu-panel]');
		const menuTrigger = card.querySelector('[data-one-profile-menu-toggle]');

		let originalBio = bioInput ? bioInput.value : '';
		let originalAvatarSrc = avatarImg ? avatarImg.src : '';
		let previewUrl = '';

		function closeProfileMenu() {
			if (menuPanel) {
				menuPanel.setAttribute('hidden', 'hidden');
				menuPanel.classList.remove('is-fixed');
				menuPanel.style.top = '';
				menuPanel.style.left = '';
				menuPanel.style.right = '';
				menuPanel.style.bottom = '';
			}
			if (menuTrigger) {
				menuTrigger.setAttribute('aria-expanded', 'false');
			}
		}

		function positionProfileMenu() {
			if (!menuTrigger || !menuPanel) {
				return;
			}
			if (window.matchMedia('(min-width: 641px)').matches) {
				menuPanel.classList.remove('is-fixed');
				menuPanel.style.top = '';
				menuPanel.style.left = '';
				menuPanel.style.right = '';
				menuPanel.style.bottom = '';
				return;
			}

			menuPanel.classList.add('is-fixed');
			const rect = menuTrigger.getBoundingClientRect();
			const panelHeight = menuPanel.offsetHeight || 120;
			const panelWidth = menuPanel.offsetWidth || 192;
			const gap = 6;
			const spaceBelow = window.innerHeight - rect.bottom;
			const openUp = spaceBelow < panelHeight + gap && rect.top > spaceBelow;

			if (openUp) {
				menuPanel.style.top = Math.max(8, rect.top - panelHeight - gap) + 'px';
			} else {
				menuPanel.style.top = Math.min(window.innerHeight - panelHeight - 8, rect.bottom + gap) + 'px';
			}
			menuPanel.style.left = Math.max(8, rect.right - panelWidth) + 'px';
			menuPanel.style.right = 'auto';
			menuPanel.style.bottom = 'auto';
		}

		function setEditing(on) {
			card.classList.toggle('is-editing', on);
			if (editForm) {
				if (on) {
					editForm.removeAttribute('hidden');
				} else {
					editForm.setAttribute('hidden', 'hidden');
				}
			}
			if (bioDisplay) {
				if (on) {
					bioDisplay.setAttribute('hidden', 'hidden');
				} else {
					bioDisplay.removeAttribute('hidden');
				}
			}
			editOnly.forEach(function (el) {
				if (on) {
					el.removeAttribute('hidden');
				} else {
					el.setAttribute('hidden', 'hidden');
				}
			});
			menuDefaultItems.forEach(function (el) {
				el.hidden = on;
			});
			if (!on) {
				closeProfileMenu();
			}
			if (on && bioInput) {
				bioInput.focus();
			}
		}

		function setSaveButtonsDisabled(disabled) {
			saveBtns.forEach(function (btn) {
				btn.disabled = disabled;
			});
		}

		function updateBioCount() {
			if (!bioInput || !bioCount) {
				return;
			}
			bioCount.textContent = String(bioInput.value.length);
		}

		if (bioInput) {
			bioInput.addEventListener('input', updateBioCount);
		}

		function saveProfile(options) {
			const opts = options || {};
			const form = new FormData();
			form.append('action', 'one1_update_profile');
			form.append('nonce', editCfg.nonce);
			form.append('bio', bioInput ? bioInput.value : '');
			if (avatarInput && avatarInput.files && avatarInput.files[0]) {
				form.append('avatar', avatarInput.files[0]);
			}

			setSaveButtonsDisabled(true);

			return fetch(editCfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
				.then(function (res) {
					return res.json();
				})
				.then(function (json) {
					if (!json.success) {
						throw new Error((json.data && json.data.message) || editCfg.i18n.error);
					}
					const bio =
						json.data.bio && json.data.bio.trim() !== ''
							? json.data.bio
							: editCfg.i18n.defaultBio;
					if (bioDisplay) {
						bioDisplay.textContent = bio;
					}
					if (bioInput) {
						bioInput.value = json.data.bio || '';
						originalBio = bioInput.value;
						updateBioCount();
					}
					if (json.data.avatar_url) {
						updateAllAvatars(json.data.avatar_url);
						originalAvatarSrc = json.data.avatar_url;
					}
					if (avatarInput) {
						avatarInput.value = '';
					}
					if (previewUrl) {
						URL.revokeObjectURL(previewUrl);
						previewUrl = '';
					}
					if (opts.exitEdit) {
						setEditing(false);
					}
					return json;
				})
				.catch(function (err) {
					window.alert((err && err.message) || editCfg.i18n.error);
					throw err;
				})
				.finally(function () {
					setSaveButtonsDisabled(false);
				});
		}

		if (avatarInput) {
			avatarInput.addEventListener('change', function () {
				const file = avatarInput.files && avatarInput.files[0];
				if (!file || !avatarImg) {
					return;
				}
				if (previewUrl) {
					URL.revokeObjectURL(previewUrl);
				}
				previewUrl = URL.createObjectURL(file);
				avatarImg.removeAttribute('srcset');
				avatarImg.removeAttribute('sizes');
				avatarImg.src = previewUrl;
				if (!card.classList.contains('is-editing')) {
					setEditing(true);
				}
				saveProfile({ exitEdit: false });
			});
		}

		if (toggleBtn) {
			toggleBtn.addEventListener('click', function () {
				closeProfileMenu();
				originalBio = bioInput ? bioInput.value : '';
				originalAvatarSrc = avatarImg ? avatarImg.src : '';
				setEditing(true);
			});
		}

		saveBtns.forEach(function (saveBtn) {
			saveBtn.addEventListener('click', function () {
				closeProfileMenu();
				saveProfile({ exitEdit: true });
			});
		});

		if (menuTrigger && menuPanel) {
			menuTrigger.addEventListener('click', function (e) {
				e.stopPropagation();
				if (menuPanel.hasAttribute('hidden')) {
					menuPanel.removeAttribute('hidden');
					menuTrigger.setAttribute('aria-expanded', 'true');
					requestAnimationFrame(positionProfileMenu);
				} else {
					closeProfileMenu();
				}
			});

			menuPanel.querySelectorAll('.one-profile-menu__item').forEach(function (item) {
				item.addEventListener('click', function () {
					closeProfileMenu();
				});
			});
		}

		document.addEventListener('click', function (e) {
			if (!e.target.closest('[data-one-profile-menu]')) {
				closeProfileMenu();
			}
		});
	}

	function handleStoryUpdated(e) {
		const detail = e && e.detail ? e.detail : {};
		const postId = detail.postId;
		const thumbnailUrl = detail.thumbnailUrl;
		if (!postId) {
			return;
		}

		if (thumbnailUrl) {
			const cell = document.querySelector('[data-one-open-post="' + postId + '"]');
			const mediaImg = cell && cell.querySelector('.one-profile-posts__media img');
			if (mediaImg) {
				mediaImg.removeAttribute('srcset');
				mediaImg.src = thumbnailUrl;
			}
		}

		const modal = document.querySelector('[data-one-post-modal]');
		if (modal && !modal.hasAttribute('hidden')) {
			openPostModal(postId);
		}
	}

	function init() {
		initProfileEdit();
		document.addEventListener('one:story-updated', handleStoryUpdated);
		document.querySelectorAll('[data-one-open-post]').forEach((btn) => {
			btn.addEventListener('click', () => {
				openPostModal(btn.getAttribute('data-one-open-post'));
			});
		});

		document.querySelectorAll('[data-one-post-modal-close]').forEach((el) => {
			el.addEventListener('click', closePostModal);
		});

		const root = document.querySelector('[data-one-post-modal]');
		if (root) {
			root.addEventListener('click', (e) => {
				if (e.target.closest('[data-one-post-modal-close]')) {
					closePostModal();
				}
			});
		}

		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') {
				closePostModal();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
