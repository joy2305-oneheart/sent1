/**
 * User account dropdown toggle.
 */
function initUserMenus() {
    const menus = document.querySelectorAll('[data-one-user-menu]');
    if (!menus.length) {
        return;
    }

    const closeAll = (except) => {
        menus.forEach((menu) => {
            if (menu === except) {
                return;
            }
            const toggle = menu.querySelector('.one-user-menu__toggle');
            const panel = menu.querySelector('.one-user-menu__dropdown');
            menu.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            if (panel) {
                panel.hidden = true;
            }
        });
    };

    menus.forEach((menu) => {
        const toggle = menu.querySelector('.one-user-menu__toggle');
        const panel = menu.querySelector('.one-user-menu__dropdown');
        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = menu.classList.contains('is-open');
            closeAll(menu);
            if (isOpen) {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                panel.hidden = true;
            } else {
                menu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                panel.hidden = false;
            }
        });
    });

    document.addEventListener('click', () => closeAll(null));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAll(null);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserMenus);
} else {
    initUserMenus();
}
