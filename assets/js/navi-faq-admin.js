(function () {
    // Seuil au-delà duquel la réponse est signalée comme "longue" pour un
    // extrait FAQPage (Google recommande des réponses concises pour un bon
    // rendu en résultat de recherche).
    var CHAR_COUNT_LONG_THRESHOLD = 300;

    // Compteur de caractères de la réponse : un seul listener global sur
    // l'évènement TinyMCE 'AddEditor', plutôt qu'un branchement séparé pour
    // les lignes rendues côté serveur (déjà initialisées au chargement) et
    // celles ajoutées en JS (wp.editor.initialize()) — capte les deux sans
    // dupliquer la logique.
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function (event) {
            var mceEditor = event.editor;
            if (!mceEditor.id || 0 !== mceEditor.id.indexOf('navi_faq_answer_')) {
                return;
            }
            var counter = document.querySelector('.navi-faq-char-count[data-editor="' + mceEditor.id + '"]');
            if (!counter) {
                return;
            }
            function updateCount() {
                var length = mceEditor.getContent({ format: 'text' }).trim().length;
                var isLong = length > CHAR_COUNT_LONG_THRESHOLD;
                var template = isLong ? naviFaqAdminI18n.charCountLong : naviFaqAdminI18n.charCount;
                counter.textContent = template.replace('%d', length);
                counter.classList.toggle('is-long', isLong);
            }
            mceEditor.on('init keyup change SetContent', updateCount);
        });
    }

    document.querySelectorAll('.navi-faq-editor').forEach(function (editor) {
        var prefix = editor.getAttribute('data-prefix');
        var emptyLabel = editor.getAttribute('data-empty-label') || '';
        var rows = editor.querySelector('.navi-faq-rows');
        var addBtn = editor.querySelector('.navi-faq-add-row');
        var status = editor.querySelector('.navi-faq-status');
        var nextNumber = parseInt(editor.getAttribute('data-next-number'), 10) || 1;

        // Un éditeur TinyMCE initialisé pendant que son conteneur est caché
        // (display:none) se retrouve avec une largeur/hauteur nulle — cas
        // classique de l'onglet "FAQ (Navi)" dans "Données produit"
        // (WooCommerce masque tous les panneaux sauf celui actif via une
        // classe "hidden") et, désormais, d'une ligne repliée par défaut.
        // Un évènement resize forcé quand le conteneur redevient visible
        // suffit à faire recalculer sa mise en page par TinyMCE.
        function refreshLayout() {
            window.dispatchEvent(new Event('resize'));
        }

        var wooPanel = editor.closest('.woocommerce_options_panel');
        if (wooPanel && typeof MutationObserver !== 'undefined') {
            var panelWasHidden = wooPanel.classList.contains('hidden');
            new MutationObserver(function () {
                var isHidden = wooPanel.classList.contains('hidden');
                if (panelWasHidden && !isHidden) {
                    refreshLayout();
                }
                panelWasHidden = isHidden;
            }).observe(wooPanel, { attributes: true, attributeFilter: ['class'] });
        }

        function announce(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function renumberTitles() {
            var titles = rows.querySelectorAll('.navi-faq-row-title');
            titles.forEach(function (title, index) {
                title.textContent = naviFaqAdminI18n.questionNumber.replace('%d', index + 1);
            });

            var empty = rows.querySelector('.navi-faq-empty');
            if (0 === titles.length) {
                if (!empty) {
                    empty = document.createElement('p');
                    empty.className = 'navi-faq-empty';
                    empty.textContent = emptyLabel;
                    rows.appendChild(empty);
                }
            } else if (empty) {
                empty.remove();
            }
        }

        // wp.editor.initialize()/remove() : instancie/détruit un véritable
        // éditeur TinyMCE sur la ligne, avec les mêmes réglages que
        // wp_editor() côté PHP (voir naviFaqEditorSettings, localisé depuis
        // navi_faq_editor_tinymce_settings() dans admin.php) — pour que la
        // barre d'outils soit identique, qu'une ligne vienne du serveur ou
        // d'un clic sur "Ajouter une question".
        function initEditor(editorId) {
            if (typeof wp === 'undefined' || !wp.editor) {
                return;
            }
            wp.editor.initialize(editorId, {
                tinymce: naviFaqEditorSettings.tinymce,
                quicktags: naviFaqEditorSettings.quicktags,
                mediaButtons: naviFaqEditorSettings.mediaButtons
            });
        }

        function removeEditor(editorId) {
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.remove(editorId);
            }
        }

        // Même structure accessible qu'une ligne rendue côté serveur (voir
        // navi_faq_render_row_markup(), admin.php) : role="group" +
        // aria-labelledby vers le titre, chaque champ avec un
        // label/id associés (WCAG 1.3.1/4.1.2 — un <label> sans attribut
        // "for" correspondant n'est pas programmatiquement relié à son
        // champ pour un lecteur d'écran).
        function makeRow(number, values, open) {
            values = values || {};
            var editorId = 'navi_faq_answer_' + number;
            var groupId = prefix + '_group_' + number;
            var questionId = prefix + '_question_' + number;
            var titleId = prefix + '_row_title_' + number;
            var bodyId = prefix + '_row_body_' + number;

            var row = document.createElement('div');
            row.className = 'navi-faq-row';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-labelledby', titleId);
            row.innerHTML =
                '<div class="navi-faq-row-header">' +
                    '<button type="button" class="navi-faq-row-toggle" aria-expanded="' + (open ? 'true' : 'false') + '" aria-controls="' + bodyId + '">' +
                        '<span class="navi-faq-row-title" id="' + titleId + '"></span>' +
                        '<span class="navi-faq-row-chevron" aria-hidden="true"></span>' +
                    '</button>' +
                    '<button type="button" class="navi-faq-remove-row" aria-label="' + naviFaqAdminI18n.remove + '">&times;</button>' +
                '</div>' +
                '<div class="navi-faq-row-body" id="' + bodyId + '"' + (open ? '' : ' hidden') + '>' +
                    '<p class="navi-faq-field navi-faq-field-group">' +
                        '<label for="' + groupId + '">' + naviFaqAdminI18n.group + '</label>' +
                        '<input type="text" class="widefat" id="' + groupId + '" list="navi-faq-themes-datalist" name="' + prefix + '_group[]" placeholder="' + naviFaqAdminI18n.groupPlaceholder + '" />' +
                    '</p>' +
                    '<p class="navi-faq-field">' +
                        '<label for="' + questionId + '">' + naviFaqAdminI18n.question + '</label>' +
                        '<input type="text" class="widefat" id="' + questionId + '" name="' + prefix + '_question[]" placeholder="' + naviFaqAdminI18n.questionPlaceholder + '" />' +
                    '</p>' +
                    '<div class="navi-faq-field navi-faq-field-answer">' +
                        '<label for="' + editorId + '">' + naviFaqAdminI18n.answer + '</label>' +
                        '<textarea id="' + editorId + '" class="widefat" rows="5" name="' + prefix + '_answer[]"></textarea>' +
                        '<p class="navi-faq-char-count" data-editor="' + editorId + '"></p>' +
                    '</div>' +
                '</div>';

            // Pré-remplissage (import JSON, voir importItems() plus bas) :
            // la valeur initiale du textarea est reprise telle quelle par
            // wp.editor.initialize() à son démarrage, pas besoin de rappeler
            // setContent() une fois l'éditeur prêt.
            if (values.group) {
                row.querySelector('#' + groupId).value = values.group;
            }
            if (values.question) {
                row.querySelector('#' + questionId).value = values.question;
            }
            if (values.answer) {
                row.querySelector('#' + editorId).value = values.answer;
            }

            return row;
        }

        function addRow(values, open) {
            var row = makeRow(nextNumber, values, open);
            var number = nextNumber;
            nextNumber++;
            rows.appendChild(row);
            renumberTitles();
            initEditor('navi_faq_answer_' + number);
            if (open) {
                refreshLayout();
            }
            return row;
        }

        // Détruit tous les éditeurs TinyMCE en cours puis vide la liste des
        // lignes — utilisé par l'import (voir importItems() plus bas) avant
        // de reconstruire la liste depuis le JSON collé.
        function clearRows() {
            rows.querySelectorAll('textarea[id^="navi_faq_answer_"]').forEach(function (textarea) {
                removeEditor(textarea.id);
            });
            rows.innerHTML = '';
            nextNumber = 1;
        }

        // Lit le contenu HTML réel de la réponse (TinyMCE si l'éditeur est
        // initialisé — reflète les modifications non enregistrées ; simple
        // valeur du textarea sinon, ex. juste après un import avant que
        // l'éditeur ait fini de s'initialiser).
        function readAnswerHtml(editorId) {
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                return tinymce.get(editorId).getContent();
            }
            var textarea = document.getElementById(editorId);
            return textarea ? textarea.value : '';
        }

        // Export : lit l'état actuel du formulaire (y compris non
        // enregistré) plutôt que la dernière version sauvegardée en base —
        // contrairement à "Dupliquer vers…", qui copie volontairement les
        // données déjà en base (voir navi_faq_ajax_duplicate(), admin.php).
        function exportItems() {
            var items = [];
            rows.querySelectorAll('.navi-faq-row').forEach(function (row) {
                var questionInput = row.querySelector('input[name$="_question[]"]');
                var groupInput = row.querySelector('input[name$="_group[]"]');
                var textarea = row.querySelector('textarea[id^="navi_faq_answer_"]');
                if (!questionInput || !questionInput.value.trim()) {
                    return;
                }
                items.push({
                    group: groupInput ? groupInput.value : '',
                    question: questionInput.value,
                    answer: textarea ? readAnswerHtml(textarea.id) : ''
                });
            });
            return items;
        }

        function importItems(json) {
            var parsed;
            try {
                parsed = JSON.parse(json);
            } catch {
                return { ok: false, message: naviFaqAdminI18n.importInvalidJson };
            }
            if (!Array.isArray(parsed)) {
                return { ok: false, message: naviFaqAdminI18n.importInvalidJson };
            }

            var valid = parsed.filter(function (item) {
                return item && typeof item === 'object'
                    && typeof item.question === 'string' && item.question.trim()
                    && typeof item.answer === 'string' && item.answer.trim();
            });
            if (!valid.length) {
                return { ok: false, message: naviFaqAdminI18n.importEmpty };
            }

            clearRows();
            valid.forEach(function (item, index) {
                addRow({
                    group: typeof item.group === 'string' ? item.group : '',
                    question: item.question,
                    answer: item.answer
                }, 0 === index);
            });
            announce(naviFaqAdminI18n.rowAdded);

            return { ok: true, count: valid.length };
        }

        addBtn.addEventListener('click', function () {
            // Une question qu'on vient d'ajouter est forcément dépliée : on
            // veut la remplir tout de suite, pas la rouvrir en plus.
            var row = addRow(undefined, true);
            var questionField = row.querySelector('input[name$="_question[]"]');
            if (questionField) {
                questionField.focus();
            }
            announce(naviFaqAdminI18n.rowAdded);
        });

        rows.addEventListener('click', function (event) {
            var toggle = event.target.closest('.navi-faq-row-toggle');
            if (toggle) {
                var wasOpen = 'true' === toggle.getAttribute('aria-expanded');
                var body = document.getElementById(toggle.getAttribute('aria-controls'));
                toggle.setAttribute('aria-expanded', wasOpen ? 'false' : 'true');
                if (body) {
                    body.hidden = wasOpen;
                    if (!wasOpen) {
                        refreshLayout();
                    }
                }
                return;
            }

            if (!event.target.classList.contains('navi-faq-remove-row')) {
                return;
            }
            if (!window.confirm(naviFaqAdminI18n.confirmRemove)) {
                return;
            }
            var row = event.target.closest('.navi-faq-row');
            var textarea = row.querySelector('textarea[id^="navi_faq_answer_"]');
            if (textarea) {
                removeEditor(textarea.id);
            }
            row.remove();
            renumberTitles();
            announce(naviFaqAdminI18n.rowRemoved);
        });

        var exportBtn = editor.querySelector('.navi-faq-export-btn');
        var importBtn = editor.querySelector('.navi-faq-import-btn');
        var importTextarea = editor.querySelector('.navi-faq-import-textarea');
        var importStatus = editor.querySelector('.navi-faq-import-status');

        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                var items = exportItems();
                if (!items.length) {
                    importStatus.textContent = naviFaqAdminI18n.exportEmpty;
                    importStatus.classList.remove('is-error');
                    return;
                }
                var blob = new Blob([JSON.stringify(items, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = 'navi-faq-' + prefix + '.json';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });
        }

        if (importBtn) {
            importBtn.addEventListener('click', function () {
                var json = importTextarea.value.trim();
                if (!json) {
                    return;
                }
                if (!window.confirm(naviFaqAdminI18n.importConfirm)) {
                    return;
                }
                var result = importItems(json);
                importStatus.classList.toggle('is-error', !result.ok);
                importStatus.textContent = result.ok
                    ? naviFaqAdminI18n.importSuccess.replace('%d', result.count)
                    : result.message;
                if (result.ok) {
                    importTextarea.value = '';
                }
            });
        }

        renumberTitles();
    });

    // --- "Dupliquer vers…" : appel AJAX, la sauvegarde est immédiate côté
    // serveur (voir navi_faq_ajax_duplicate(), admin.php) — n'affecte pas
    // le formulaire actuellement ouvert, seulement la destination choisie. ---
    document.querySelectorAll('.navi-faq-duplicate-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrapper = btn.closest('.navi-faq-duplicate');
            var select = wrapper.querySelector('.navi-faq-duplicate-target');
            var status = wrapper.querySelector('.navi-faq-duplicate-status');
            var target = select.value;

            if (!target) {
                status.textContent = naviFaqAdminI18n.duplicateChooseTarget;
                status.classList.remove('is-error');
                return;
            }
            if (!window.confirm(naviFaqAdminI18n.duplicateConfirm)) {
                return;
            }

            btn.disabled = true;
            status.classList.remove('is-error');
            status.textContent = naviFaqAdminI18n.duplicateInProgress;

            var data = new FormData();
            data.append('action', 'navi_faq_duplicate');
            data.append('nonce', btn.getAttribute('data-nonce'));
            data.append('source', btn.getAttribute('data-source'));
            data.append('target', target);

            fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    var message = result.data && result.data.message ? result.data.message : '';
                    status.textContent = message;
                    status.classList.toggle('is-error', !result.success);
                })
                .catch(function () {
                    status.textContent = naviFaqAdminI18n.duplicateError;
                    status.classList.add('is-error');
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    });
})();
