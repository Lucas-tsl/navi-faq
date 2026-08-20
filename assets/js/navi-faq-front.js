(function () {
    // --- Accordéon : chaque question s'ouvre/se ferme indépendamment ---
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.navi-faq-question');
        if (!button) {
            return;
        }
        var answer = button.nextElementSibling;
        if (!answer) {
            return;
        }
        var expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        answer.classList.toggle('is-open', !expanded);
    });

    // --- Onglets par thème : clic + navigation clavier flèches gauche/droite
    // (pattern ARIA Tabs, https://www.w3.org/WAI/ARIA/apg/patterns/tabs/) ---
    function activateTab(tablist, tab) {
        var tabs = tablist.querySelectorAll('.navi-faq-tab-btn');
        tabs.forEach(function (btn) {
            var isActive = btn === tab;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.setAttribute('tabindex', isActive ? '0' : '-1');

            var panel = document.getElementById(btn.getAttribute('aria-controls'));
            if (panel) {
                panel.classList.toggle('active', isActive);
                panel.toggleAttribute('hidden', !isActive);
            }
        });
        tab.focus();
    }

    document.querySelectorAll('.navi-faq-tabs-nav').forEach(function (tablist) {
        var tabs = Array.prototype.slice.call(tablist.querySelectorAll('.navi-faq-tab-btn'));

        tablist.addEventListener('click', function (event) {
            var tab = event.target.closest('.navi-faq-tab-btn');
            if (tab) {
                activateTab(tablist, tab);
            }
        });

        tablist.addEventListener('keydown', function (event) {
            var currentIndex = tabs.indexOf(document.activeElement);
            if (currentIndex === -1) {
                return;
            }
            var nextIndex = null;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % tabs.length;
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = tabs.length - 1;
            }
            if (null !== nextIndex) {
                event.preventDefault();
                activateTab(tablist, tabs[nextIndex]);
            }
        });
    });
})();
