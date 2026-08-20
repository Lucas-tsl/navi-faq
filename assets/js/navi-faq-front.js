(function () {
    // --- Accordéon exclusif : ouvrir une question referme les autres du même
    // groupe. Le pli/dépli lui-même est natif (<details>/<summary>, voir
    // navi_faq_render_item_html() dans frontend.php) : ceci n'est qu'un
    // confort en plus, capturé en phase de capture car l'évènement natif
    // 'toggle' ne remonte pas (pas de bubbling) — la capture, elle,
    // fonctionne quel que soit le bubbling. ---
    document.querySelectorAll('.navi-faq-accordion').forEach(function (accordion) {
        accordion.addEventListener('toggle', function (event) {
            var target = event.target;
            if (!target.matches('.navi-faq-item') || !target.open) {
                return;
            }
            accordion.querySelectorAll('.navi-faq-item[open]').forEach(function (other) {
                if (other !== target) {
                    other.removeAttribute('open');
                }
            });
        }, true);
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

    // --- Ancre directe vers une question (#faq-N, voir
    // navi_faq_next_anchor_id() dans frontend.php) : la déplie et active
    // l'onglet qui la contient si elle est dans un panneau caché — sans
    // ça, le navigateur ne peut ni faire défiler jusqu'à un élément dans un
    // ancêtre [hidden], ni le dévoiler lui-même. ---
    function openFromHash() {
        var hash = window.location.hash;
        if (hash.length < 2) {
            return;
        }
        var target;
        try {
            target = document.querySelector(hash);
        } catch {
            return; // Fragment non exploitable comme sélecteur CSS.
        }
        if (!target || !target.classList.contains('navi-faq-item')) {
            return;
        }

        var panel = target.closest('.navi-faq-tab-panel');
        if (panel && panel.hasAttribute('hidden')) {
            var tab = document.getElementById(panel.getAttribute('aria-labelledby'));
            var tablist = tab ? tab.closest('.navi-faq-tabs-nav') : null;
            if (tab && tablist) {
                activateTab(tablist, tab);
            }
        }

        target.setAttribute('open', '');
        target.scrollIntoView({ block: 'center' });
    }

    openFromHash();
    window.addEventListener('hashchange', openFromHash);
})();
