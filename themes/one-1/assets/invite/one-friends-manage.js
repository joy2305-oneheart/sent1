(function () {
	'use strict';

	const cfg = typeof oneFriendsManage !== 'undefined' ? oneFriendsManage : null;
	if (!cfg) {
		return;
	}

	const root = document.querySelector('[data-one-friends-manage]');
	if (!root) {
		return;
	}

	const listEl = root.querySelector('[data-one-friends-list]');
	const countEl = root.querySelector('[data-one-friends-count]');
	const modal = root.querySelector('[data-one-friend-modal]');
	const modalForm = root.querySelector('[data-one-friend-modal-form]');
	const modalId = root.querySelector('[data-one-friend-modal-id]');
	const modalName = root.querySelector('[data-one-friend-modal-name]');
	const modalEmail = root.querySelector('[data-one-friend-modal-email]');
	const modalAvatar = root.querySelector('[data-one-friend-modal-avatar]');
	const modalJoined = root.querySelector('[data-one-friend-modal-joined]');
	const modalRole = root.querySelector('[data-one-friend-modal-role]');
	const modalNickname = root.querySelector('#one-friend-modal-nickname');
	const modalNotes = root.querySelector('#one-friend-modal-notes');
	const modalFeedback = root.querySelector('[data-one-friend-modal-feedback]');

	let friendsCache = [];
	let lastFocus = null;

	function post(action, data) {
		const form = new FormData();
		form.append('action', action);
		form.append('nonce', cfg.nonce);
		Object.keys(data).forEach((key) => form.append(key, data[key]));
		return fetch(cfg.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' }).then((res) => res.json());
	}

	function formatDate(iso) {
		if (!iso) {
			return '—';
		}
		try {
			return new Date(iso.replace(' ', 'T') + 'Z').toLocaleDateString();
		} catch (e) {
			return iso;
		}
	}

	function escapeHtml(str) {
		return String(str || '').replace(/[&<>"']/g, (c) => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
		}[c]));
	}

	function roleLabel(role) {
		if (role === 'friend') {
			return cfg.i18n.roleFriend || 'Friend';
		}
		if (role === 'pu') {
			return cfg.i18n.rolePu || 'Primary User';
		}
		return role;
	}

	function renderFriends(friends) {
		friendsCache = friends;
		if (!listEl) {
			return;
		}
		if (!friends.length) {
			listEl.innerHTML = '<li class="one-friends-manage__empty">' + escapeHtml(root.getAttribute('data-empty')) + '</li>';
			return;
		}

		const editLabel = root.getAttribute('data-edit-label') || 'Edit friend details';

		listEl.innerHTML = friends.map((friend) => {
			const label = friend.nickname || friend.display_name;
			const subline = friend.nickname ? friend.display_name : friend.email;
			const avatar = friend.avatar_url
				? '<img class="one-friends-manage__avatar" src="' + escapeHtml(friend.avatar_url) + '" alt="" width="40" height="40" />'
				: '<span class="one-friends-manage__avatar one-friends-manage__avatar--fallback" aria-hidden="true">' + escapeHtml(label.charAt(0).toUpperCase()) + '</span>';

			return `
				<li class="one-friends-manage__item" data-friend-id="${friend.id}">
					${avatar}
					<div class="one-friends-manage__info">
						<p class="one-friends-manage__name">${escapeHtml(label)}</p>
						<p class="one-friends-manage__meta">${escapeHtml(subline)}</p>
					</div>
					<button
						type="button"
						class="one-friends-manage__menu"
						data-one-friend-edit
						data-friend-id="${friend.id}"
						aria-label="${escapeHtml(editLabel)}"
					>
						<span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
					</button>
				</li>`;
		}).join('');
	}

	function openModal(friend) {
		if (!modal || !friend) {
			return;
		}
		lastFocus = document.activeElement;

		const label = friend.nickname || friend.display_name;
		if (modalName) {
			modalName.textContent = label;
		}
		if (modalEmail) {
			modalEmail.textContent = friend.email;
		}
		if (modalAvatar) {
			if (friend.avatar_url) {
				modalAvatar.src = friend.avatar_url;
				modalAvatar.alt = label;
				modalAvatar.hidden = false;
			} else {
				modalAvatar.hidden = true;
			}
		}
		if (modalJoined) {
			modalJoined.textContent = formatDate(friend.joined);
		}
		if (modalRole) {
			modalRole.textContent = roleLabel(friend.role);
		}
		if (modalId) {
			modalId.value = String(friend.id);
		}
		if (modalNickname) {
			modalNickname.value = friend.nickname || '';
		}
		if (modalNotes) {
			modalNotes.value = friend.notes || '';
		}
		if (modalFeedback) {
			modalFeedback.hidden = true;
			modalFeedback.textContent = '';
			modalFeedback.classList.remove('is-error');
		}

		modal.removeAttribute('hidden');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('one-friend-modal-open');
		if (modalNickname) {
			setTimeout(() => modalNickname.focus(), 60);
		}
	}

	function closeModal() {
		if (!modal) {
			return;
		}
		modal.setAttribute('hidden', 'hidden');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('one-friend-modal-open');
		if (lastFocus && typeof lastFocus.focus === 'function') {
			lastFocus.focus();
		}
	}

	async function loadFriends() {
		const hasPrerendered = listEl && listEl.querySelector('.one-friends-manage__item');
		if (listEl && !hasPrerendered) {
			listEl.innerHTML = '<li class="one-friends-manage__loading">' + escapeHtml(cfg.i18n.loading) + '</li>';
		}
		const res = await post('sin_get_friends_list', {});
		if (!res.success) {
			if (listEl && !hasPrerendered) {
				listEl.innerHTML = '<li class="one-friends-manage__empty">' + escapeHtml(res.data && res.data.message ? res.data.message : cfg.i18n.error) + '</li>';
			}
			return;
		}
		const friends = res.data.friends || [];
		if (countEl) {
			countEl.textContent = String(typeof res.data.count === 'number' ? res.data.count : friends.length);
		}
		renderFriends(friends);
	}

	root.addEventListener('click', (event) => {
		const editBtn = event.target.closest('[data-one-friend-edit]');
		if (editBtn) {
			const friendId = parseInt(editBtn.getAttribute('data-friend-id'), 10);
			const friend = friendsCache.find((f) => f.id === friendId);
			if (friend) {
				openModal(friend);
			}
			return;
		}

		if (event.target.closest('[data-one-friend-modal-close]')) {
			closeModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && modal && modal.getAttribute('aria-hidden') === 'false') {
			closeModal();
		}
	});

	if (modalForm) {
		modalForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const submit = modalForm.querySelector('[type="submit"]');
			if (submit) {
				submit.disabled = true;
			}
			try {
				const res = await post('sin_save_friend_details', {
					friend_id: modalId ? modalId.value : '',
					nickname: modalNickname ? modalNickname.value : '',
					notes: modalNotes ? modalNotes.value : '',
				});
				if (modalFeedback) {
					modalFeedback.hidden = false;
					modalFeedback.textContent = res.success
						? cfg.i18n.saved
						: (res.data && res.data.message ? res.data.message : cfg.i18n.error);
					modalFeedback.classList.toggle('is-error', !res.success);
				}
				if (res.success) {
					await loadFriends();
					setTimeout(closeModal, 500);
				}
			} catch (err) {
				if (modalFeedback) {
					modalFeedback.hidden = false;
					modalFeedback.textContent = cfg.i18n.error;
					modalFeedback.classList.add('is-error');
				}
			} finally {
				if (submit) {
					submit.disabled = false;
				}
			}
		});
	}

	loadFriends();

	// Seed cache from server-rendered list so edit menu works before AJAX refresh.
	if (listEl) {
		listEl.querySelectorAll('[data-friend-id]').forEach((item) => {
			const friendId = parseInt(item.getAttribute('data-friend-id'), 10);
			if (!friendId) {
				return;
			}
			const nameEl = item.querySelector('.one-friends-manage__name');
			const metaEl = item.querySelector('.one-friends-manage__meta');
			const avatarEl = item.querySelector('.one-friends-manage__avatar');
			const name = nameEl ? nameEl.textContent.trim() : '';
			const meta = metaEl ? metaEl.textContent.trim() : '';
			const avatarUrl = avatarEl && avatarEl.tagName === 'IMG' ? avatarEl.getAttribute('src') : '';
			friendsCache.push({
				id: friendId,
				display_name: meta,
				email: meta.indexOf('@') !== -1 ? meta : '',
				nickname: name !== meta ? name : '',
				notes: '',
				joined: '',
				role: 'friend',
				avatar_url: avatarUrl || '',
			});
		});
	}
})();
