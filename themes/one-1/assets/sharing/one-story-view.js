/**
 * Story view — comments and engage controls.
 */
(function () {
	'use strict';

	const cfg = window.oneStoryView || {};

	function bindCommentForms(root) {
		const scope = root || document;
		scope.querySelectorAll('[data-one-story-comment-form]').forEach((form) => {
			if (form.dataset.oneStoryCommentBound) {
				return;
			}
			form.dataset.oneStoryCommentBound = '1';

			const section = form.closest('[data-one-story-comments]');
			const postId = section ? section.getAttribute('data-post-id') : '';
			const list = section ? section.querySelector('[data-one-story-comments-list]') : null;
			const empty = section ? section.querySelector('[data-one-story-comments-empty]') : null;
			const countEl = section ? section.querySelector('[data-one-comment-count]') : null;
			const feedback = form.querySelector('[data-one-story-comment-feedback]');
			const submitBtn = form.querySelector('[data-one-story-comment-submit]');
			const textarea = form.querySelector('textarea');

			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				if (!postId || !cfg.ajaxUrl) {
					return;
				}

				const text = textarea ? textarea.value.trim() : '';
				if (!text) {
					return;
				}

				if (submitBtn) {
					submitBtn.disabled = true;
				}
				if (feedback) {
					feedback.setAttribute('hidden', 'hidden');
				}

				const body = new FormData();
				body.append('action', 'one_story_add_comment');
				body.append('nonce', cfg.commentNonce || '');
				body.append('post_id', postId);
				body.append('comment', text);

				try {
					const res = await fetch(cfg.ajaxUrl, {
						method: 'POST',
						body,
						credentials: 'same-origin',
					});
					const json = await res.json();
					if (!json.success) {
						throw new Error((json.data && json.data.message) || cfg.i18n.error);
					}

					if (textarea) {
						textarea.value = '';
					}
					if (list && json.data && json.data.html) {
						list.removeAttribute('hidden');
						list.insertAdjacentHTML('beforeend', json.data.html);
					}
					if (empty) {
						empty.setAttribute('hidden', 'hidden');
					}
					if (countEl && json.data && typeof json.data.count !== 'undefined') {
						countEl.textContent = String(json.data.count);
					}
				} catch (err) {
					if (feedback) {
						feedback.textContent = err.message || cfg.i18n.error;
						feedback.removeAttribute('hidden');
					}
				} finally {
					if (submitBtn) {
						submitBtn.disabled = false;
					}
				}
			});
		});
	}

	function positionOwnerPanel(trigger, panel) {
		const inPostModal = trigger.closest('[data-one-post-modal]');
		const inFeedCard = trigger.closest('.sent-share-card');
		if (!inPostModal && !inFeedCard) {
			panel.classList.remove('is-fixed');
			panel.style.top = '';
			panel.style.bottom = '';
			panel.style.left = '';
			panel.style.right = '';
			return;
		}

		panel.classList.add('is-fixed');
		const triggerRect = trigger.getBoundingClientRect();
		const panelHeight = panel.offsetHeight || 220;
		const gap = 6;
		const spaceBelow = window.innerHeight - triggerRect.bottom;
		const spaceAbove = triggerRect.top;
		const openUp = spaceBelow < panelHeight + gap && spaceAbove > spaceBelow;

		if (openUp) {
			panel.style.top = Math.max(8, triggerRect.top - panelHeight - gap) + 'px';
		} else {
			panel.style.top = Math.min(window.innerHeight - panelHeight - 8, triggerRect.bottom + gap) + 'px';
		}

		panel.style.left = Math.max(8, triggerRect.right - panel.offsetWidth) + 'px';
		panel.style.right = 'auto';
		panel.style.bottom = 'auto';
	}

	function bindOwnerMenus(root) {
		const scope = root || document;
		scope.querySelectorAll('[data-one-story-owner-menu]').forEach((menu) => {
			if (menu.dataset.oneStoryOwnerMenuBound) {
				return;
			}
			menu.dataset.oneStoryOwnerMenuBound = '1';

			const trigger = menu.querySelector('[data-one-story-owner-menu-toggle]');
			const panel = menu.querySelector('[data-one-story-owner-menu-panel]');
			if (!trigger || !panel) {
				return;
			}

			const close = () => {
				panel.setAttribute('hidden', 'hidden');
				trigger.setAttribute('aria-expanded', 'false');
				panel.classList.remove('is-fixed');
				panel.style.top = '';
				panel.style.bottom = '';
				panel.style.left = '';
				panel.style.right = '';
			};

			const open = () => {
				document.querySelectorAll('[data-one-story-owner-menu-panel]').forEach((other) => {
					if (other !== panel) {
						other.setAttribute('hidden', 'hidden');
						const otherTrigger = other.closest('[data-one-story-owner-menu]')?.querySelector('[data-one-story-owner-menu-toggle]');
						if (otherTrigger) {
							positionOwnerPanel(otherTrigger, other);
						}
					}
				});
				document.querySelectorAll('[data-one-story-owner-menu-toggle]').forEach((other) => {
					if (other !== trigger) {
						other.setAttribute('aria-expanded', 'false');
					}
				});
				panel.removeAttribute('hidden');
				trigger.setAttribute('aria-expanded', 'true');
				requestAnimationFrame(() => positionOwnerPanel(trigger, panel));
			};

			trigger.addEventListener('click', (e) => {
				e.stopPropagation();
				if (panel.hasAttribute('hidden')) {
					open();
				} else {
					close();
				}
			});

			panel.querySelectorAll('button[role="menuitem"]').forEach((item) => {
				item.addEventListener('click', () => {
					close();
				});
			});
		});
	}

	function bindOwnerEdit(root) {
		const scope = root || document;
		scope.querySelectorAll('[data-one-story-edit]').forEach((btn) => {
			if (btn.dataset.oneStoryEditBound) {
				return;
			}
			btn.dataset.oneStoryEditBound = '1';

			btn.addEventListener('click', () => {
				const postId = btn.getAttribute('data-post-id');
				if (!postId || !window.OneComposer || typeof window.OneComposer.openForEdit !== 'function') {
					return;
				}

				const modal = document.querySelector('[data-one-post-modal]');
				if (modal && !modal.hasAttribute('hidden')) {
					const closeBtn = modal.querySelector('[data-one-post-modal-close]');
					if (closeBtn) {
						closeBtn.click();
					}
				}

				window.OneComposer.openForEdit(postId);
			});
		});
	}

	function bindOwnerActions(root) {
		const scope = root || document;
		scope.querySelectorAll('[data-one-story-archive], [data-one-story-delete]').forEach((btn) => {
			if (btn.dataset.oneStoryRemoveBound) {
				return;
			}
			btn.dataset.oneStoryRemoveBound = '1';

			btn.addEventListener('click', async () => {
				if (!cfg.ajaxUrl || !cfg.removeNonce) {
					return;
				}

				const postId = btn.getAttribute('data-post-id');
				const mode = btn.hasAttribute('data-one-story-archive') ? 'archive' : 'delete';
				const confirmMsg =
					mode === 'archive'
						? cfg.i18n.archiveConfirm
						: cfg.i18n.deleteConfirm;

				const confirmFn =
					window.OneConfirm && typeof window.OneConfirm.ask === 'function'
						? window.OneConfirm.ask.bind(window.OneConfirm)
						: function (message) {
								return Promise.resolve(window.confirm(message));
						  };

				const confirmed = await confirmFn(confirmMsg, {
					title: mode === 'archive' ? cfg.i18n.archiveTitle || 'Archive post' : cfg.i18n.deleteTitle || 'Delete post',
					confirmLabel: mode === 'archive' ? cfg.i18n.archiveOk || 'Archive' : cfg.i18n.deleteOk || 'Delete',
					danger: mode === 'delete',
				});

				if (!confirmed) {
					return;
				}

				btn.disabled = true;

				const body = new FormData();
				body.append('action', 'one_story_remove');
				body.append('nonce', cfg.removeNonce);
				body.append('post_id', postId);
				body.append('mode', mode);

				try {
					const res = await fetch(cfg.ajaxUrl, {
						method: 'POST',
						body,
						credentials: 'same-origin',
					});
					const json = await res.json();
					if (!json.success) {
						throw new Error((json.data && json.data.message) || cfg.i18n.error);
					}

					document.querySelectorAll('[data-one-open-post="' + postId + '"]').forEach((cell) => {
						const item = cell.closest('.one-profile-posts__cell, [role="listitem"]');
						if (item) {
							item.remove();
						}
					});

					const feedCard = document.querySelector('.sent-share-card[data-story-id="' + postId + '"]');
					if (feedCard) {
						feedCard.remove();
						return;
					}

					const modal = document.querySelector('[data-one-post-modal]');
					if (modal && !modal.hasAttribute('hidden')) {
						const closeBtn = modal.querySelector('[data-one-post-modal-close]');
						if (closeBtn) {
							closeBtn.click();
						}
					}

					if (mode === 'archive' && cfg.i18n.archiveSuccess) {
						window.alert(cfg.i18n.archiveSuccess);
					} else if (mode === 'delete' && cfg.i18n.deleteSuccess) {
						window.alert(cfg.i18n.deleteSuccess);
					}

					if (window.location.pathname.indexOf('/stories/') !== -1) {
						window.location.href = cfg.profileUrl || '/profile/';
						return;
					}
				} catch (err) {
					window.alert(err.message || cfg.i18n.error);
					btn.disabled = false;
				}
			});
		});
	}

	function init() {
		bindCommentForms(document);
		bindOwnerMenus(document);
		bindOwnerEdit(document);
		bindOwnerActions(document);
	}

	document.addEventListener('one:story-view-loaded', (e) => {
		if (e.detail && e.detail.root) {
			bindCommentForms(e.detail.root);
			bindOwnerMenus(e.detail.root);
			bindOwnerEdit(e.detail.root);
			bindOwnerActions(e.detail.root);
		}
	});

	document.addEventListener('click', (e) => {
		if (!e.target.closest('[data-one-story-owner-menu]')) {
			document.querySelectorAll('[data-one-story-owner-menu-panel]').forEach((panel) => {
				panel.setAttribute('hidden', 'hidden');
			});
			document.querySelectorAll('[data-one-story-owner-menu-toggle]').forEach((trigger) => {
				trigger.setAttribute('aria-expanded', 'false');
			});
		}
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') {
			document.querySelectorAll('[data-one-story-owner-menu-panel]').forEach((panel) => {
				panel.setAttribute('hidden', 'hidden');
			});
			document.querySelectorAll('[data-one-story-owner-menu-toggle]').forEach((trigger) => {
				trigger.setAttribute('aria-expanded', 'false');
			});
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
