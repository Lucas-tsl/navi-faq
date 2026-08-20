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
        function makeRow(number) {
            var editorId = 'navi_faq_answer_' + number;
            var groupId = prefix + '_group_' + number;
            var questionId = prefix + '_question_' + number;
            var titleId = prefix + '_row_title_' + number;

            var row = document.createElement('div');
            row.className = 'navi-faq-row';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-labelledby', titleId);
            row.innerHTML =
                '<div class="navi-faq-row-header">' +
                    '<span class="navi-faq-row-title" id="' + titleId + '"></span>' +
                    '<button type="button" class="navi-faq-remove-row" aria-label="' + naviFaqAdminI18n.remove + '">&times;</button>' +
                '</div>' +
                '<div class="navi-faq-row-body">' +
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

            return row;
        }

        function addRow() {
            var row = makeRow(nextNumber);
            var number = nextNumber;
            nextNumber++;
            rows.appendChild(row);
            renumberTitles();
            initEditor('navi_faq_answer_' + number);
            return row;
        }

        addBtn.addEventListener('click', function () {
            var row = addRow();
            var questionField = row.querySelector('input[name$="_question[]"]');
            if (questionField) {
                questionField.focus();
            }
            announce(naviFaqAdminI18n.rowAdded);
        });

        rows.addEventListener('click', function (event) {
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
