(function () {
    // Paliers du compteur de mots de la réponse (extrait FAQPage : Google
    // favorise des réponses concises). "warning" = encore acceptable mais
    // on commence à délayer ; au-delà de "danger", mieux vaut raccourcir.
    var WORD_COUNT_WARNING_AT = 40;
    var WORD_COUNT_DANGER_AT = 70;

    function countWords(text) {
        text = text.trim();
        return text ? text.split(/\s+/).length : 0;
    }

    function wordCountStage(count) {
        if (count > WORD_COUNT_DANGER_AT) {
            return 'danger';
        }
        if (count > WORD_COUNT_WARNING_AT) {
            return 'warning';
        }
        return 'good';
    }

    // Compteur de mots de la réponse : un seul listener global sur
    // l'évènement TinyMCE 'AddEditor', plutôt qu'un branchement séparé pour
    // les lignes rendues côté serveur (déjà initialisées au chargement) et
    // celles ajoutées en JS (wp.editor.initialize()) — capte les deux sans
    // dupliquer la logique.
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function (event) {
            var mceEditor = event.editor;
            if (!mceEditor.id || 0 !== mceEditor.id.indexOf('saito_faq_answer_')) {
                return;
            }
            var counter = document.querySelector('.saito-faq-char-count[data-editor="' + mceEditor.id + '"]');
            if (!counter) {
                return;
            }
            function updateCount() {
                var words = countWords(mceEditor.getContent({ format: 'text' }));
                var stage = wordCountStage(words);
                var template = naviFaqAdminI18n['wordCount_' + stage];
                counter.textContent = template.replace('%d', words);
                counter.classList.remove('is-good', 'is-warning', 'is-danger');
                counter.classList.add('is-' + stage);
            }
            mceEditor.on('init keyup change SetContent', updateCount);
        });
    }

    document.querySelectorAll('.saito-faq-editor').forEach(function (editor) {
        var prefix = editor.getAttribute('data-prefix');
        var emptyLabel = editor.getAttribute('data-empty-label') || '';
        var rows = editor.querySelector('.saito-faq-rows');
        var addBtn = editor.querySelector('.saito-faq-add-row');
        var status = editor.querySelector('.saito-faq-status');
        var nextNumber = parseInt(editor.getAttribute('data-next-number'), 10) || 1;

        function announce(message) {
            if (status) {
                status.textContent = message;
            }
        }

        function renumberTitles() {
            var titles = rows.querySelectorAll('.saito-faq-row-title');
            titles.forEach(function (title, index) {
                title.textContent = naviFaqAdminI18n.questionNumber.replace('%d', index + 1);
            });

            var empty = rows.querySelector('.saito-faq-empty');
            if (0 === titles.length) {
                if (!empty) {
                    empty = document.createElement('p');
                    empty.className = 'saito-faq-empty';
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
        // saito_faq_editor_tinymce_settings() dans admin.php) — pour que la
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

        // Une ligne repliée par défaut au chargement (toutes sauf la
        // première, voir saito_faq_render_editor_ui(), admin.php) a son
        // éditeur TinyMCE initialisé pendant que son conteneur est encore
        // caché (hidden) : il se retrouve avec une largeur/hauteur nulle.
        // Détruire puis réinitialiser l'éditeur une fois la ligne visible
        // corrige la mise en page — sans passer par un évènement "resize"
        // global, qui fait aussi réagir editor-expand.js (barre d'outils
        // collante de l'éditeur classique WordPress, présent sur les fiches
        // produits mais pas sur les catégories) et provoquait le saut de
        // défilement vers le haut de la page constaté uniquement là. On ne
        // le fait qu'une fois par ligne (data-editor-fixed) : au dépli
        // suivant, l'éditeur est déjà correctement dimensionné et une
        // réinitialisation ferait juste perdre le focus/l'historique
        // d'annulation en cours pour rien.
        function fixEditorLayoutOnce(body) {
            if (!body || body.hasAttribute('data-editor-fixed')) {
                return;
            }
            body.setAttribute('data-editor-fixed', 'true');
            var textarea = body.querySelector('textarea[id^="saito_faq_answer_"]');
            if (!textarea || typeof tinymce === 'undefined' || !tinymce.get(textarea.id)) {
                return;
            }
            var editorId = textarea.id;
            removeEditor(editorId);
            initEditor(editorId);
        }

        // Même structure accessible qu'une ligne rendue côté serveur (voir
        // saito_faq_render_row_markup(), admin.php) : role="group" +
        // aria-labelledby vers le titre, chaque champ avec un
        // label/id associés (WCAG 1.3.1/4.1.2 — un <label> sans attribut
        // "for" correspondant n'est pas programmatiquement relié à son
        // champ pour un lecteur d'écran).
        function makeRow(number, values, open) {
            values = values || {};
            var editorId = 'saito_faq_answer_' + number;
            var groupId = prefix + '_group_' + number;
            var questionId = prefix + '_question_' + number;
            var titleId = prefix + '_row_title_' + number;
            var bodyId = prefix + '_row_body_' + number;

            var row = document.createElement('div');
            row.className = 'saito-faq-row';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-labelledby', titleId);
            row.innerHTML =
                '<div class="saito-faq-row-header">' +
                    '<button type="button" class="saito-faq-row-toggle" aria-expanded="' + (open ? 'true' : 'false') + '" aria-controls="' + bodyId + '">' +
                        '<span class="saito-faq-row-title" id="' + titleId + '"></span>' +
                        '<span class="saito-faq-row-chevron" aria-hidden="true"></span>' +
                    '</button>' +
                    '<button type="button" class="saito-faq-remove-row" aria-label="' + naviFaqAdminI18n.remove + '">&times;</button>' +
                '</div>' +
                '<div class="saito-faq-row-body" id="' + bodyId + '"' + (open ? '' : ' hidden') + '>' +
                    '<p class="saito-faq-field saito-faq-field-group">' +
                        '<label for="' + groupId + '">' + naviFaqAdminI18n.group + '</label>' +
                        '<input type="text" class="widefat" id="' + groupId + '" list="saito-faq-themes-datalist" name="' + prefix + '_group[]" placeholder="' + naviFaqAdminI18n.groupPlaceholder + '" />' +
                    '</p>' +
                    '<p class="saito-faq-field">' +
                        '<label for="' + questionId + '">' + naviFaqAdminI18n.question + '</label>' +
                        '<input type="text" class="widefat" id="' + questionId + '" name="' + prefix + '_question[]" placeholder="' + naviFaqAdminI18n.questionPlaceholder + '" />' +
                    '</p>' +
                    '<div class="saito-faq-field saito-faq-field-answer">' +
                        '<label for="' + editorId + '">' + naviFaqAdminI18n.answer + '</label>' +
                        '<textarea id="' + editorId + '" class="widefat" rows="5" name="' + prefix + '_answer[]"></textarea>' +
                        '<p class="saito-faq-char-count" data-editor="' + editorId + '"></p>' +
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
            initEditor('saito_faq_answer_' + number);
            if (open) {
                // Jamais cachée à la création : pas besoin de
                // fixEditorLayoutOnce(), mais on marque quand même la ligne
                // pour ne pas la retraiter inutilement à un futur repli/dépli.
                row.querySelector('.saito-faq-row-body').setAttribute('data-editor-fixed', 'true');
            }
            return row;
        }

        // Détruit tous les éditeurs TinyMCE en cours puis vide la liste des
        // lignes — utilisé par l'import (voir importItems() plus bas) avant
        // de reconstruire la liste depuis le JSON collé.
        function clearRows() {
            rows.querySelectorAll('textarea[id^="saito_faq_answer_"]').forEach(function (textarea) {
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
        // données déjà en base (voir saito_faq_ajax_duplicate(), admin.php).
        function exportItems() {
            var items = [];
            rows.querySelectorAll('.saito-faq-row').forEach(function (row) {
                var questionInput = row.querySelector('input[name$="_question[]"]');
                var groupInput = row.querySelector('input[name$="_group[]"]');
                var textarea = row.querySelector('textarea[id^="saito_faq_answer_"]');
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
            var toggle = event.target.closest('.saito-faq-row-toggle');
            if (toggle) {
                event.preventDefault();
                var wasOpen = 'true' === toggle.getAttribute('aria-expanded');
                var body = document.getElementById(toggle.getAttribute('aria-controls'));
                toggle.setAttribute('aria-expanded', wasOpen ? 'false' : 'true');
                if (body) {
                    body.hidden = wasOpen;
                    if (!wasOpen) {
                        fixEditorLayoutOnce(body);
                    }
                }
                return;
            }

            if (!event.target.classList.contains('saito-faq-remove-row')) {
                return;
            }
            if (!window.confirm(naviFaqAdminI18n.confirmRemove)) {
                return;
            }
            var row = event.target.closest('.saito-faq-row');
            var textarea = row.querySelector('textarea[id^="saito_faq_answer_"]');
            if (textarea) {
                removeEditor(textarea.id);
            }
            row.remove();
            renumberTitles();
            announce(naviFaqAdminI18n.rowRemoved);
        });

        var exportBtn = editor.querySelector('.saito-faq-export-btn');
        var importBtn = editor.querySelector('.saito-faq-import-btn');
        var importFile = editor.querySelector('.saito-faq-import-file');
        var importTextarea = editor.querySelector('.saito-faq-import-textarea');
        var importStatus = editor.querySelector('.saito-faq-import-status');

        // Choisir un fichier remplit simplement la zone de texte : on garde
        // un seul chemin de code pour valider/importer (le clic sur
        // "Importer" reste nécessaire, avec sa confirmation).
        if (importFile) {
            importFile.addEventListener('change', function () {
                var file = importFile.files[0];
                if (!file) {
                    return;
                }
                var reader = new FileReader();
                reader.onload = function () {
                    importTextarea.value = String(reader.result || '');
                    importStatus.textContent = '';
                    importStatus.classList.remove('is-error');
                };
                reader.onerror = function () {
                    importStatus.textContent = naviFaqAdminI18n.importFileError;
                    importStatus.classList.add('is-error');
                };
                reader.readAsText(file);
            });
        }

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
                link.download = 'saito-faq-' + prefix + '.json';
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
                    if (importFile) {
                        importFile.value = '';
                    }
                }
            });
        }

        // La ligne rendue ouverte au chargement (la première, voir
        // saito_faq_render_editor_ui(), admin.php) n'a jamais été cachée :
        // son éditeur est déjà correctement dimensionné, pas besoin de
        // fixEditorLayoutOnce() à un futur dépli.
        rows.querySelectorAll('.saito-faq-row-body:not([hidden])').forEach(function (body) {
            body.setAttribute('data-editor-fixed', 'true');
        });

        renumberTitles();
    });

    // --- Recherche dans la liste des destinations (seulement affichée au-delà
    // de 5 destinations, voir saito_faq_render_duplicate_ui(), admin.php) :
    // filtre les libellés côté client, insensible à la casse et aux accents
    // (ex. "vetements" retrouve "Vêtements"). ---
    // Plage Unicode des signes diacritiques combinants (U+0300-U+036F) :
    // construite via les codes des caractères plutôt qu'un échappement
    // \uXXXX écrit en dur dans le code, pour éviter tout souci
    // d'encodage/affichage de ces caractères combinants dans le fichier
    // source lui-même.
    var DIACRITICS_PATTERN = new RegExp(
        '[' + String.fromCharCode( 0x0300 ) + '-' + String.fromCharCode( 0x036f ) + ']',
        'g'
    );

    function normalizeForSearch( str ) {
        return str.toLowerCase().normalize( 'NFD' ).replace( DIACRITICS_PATTERN, '' );
    }

    document.querySelectorAll( '.saito-faq-duplicate' ).forEach( function ( wrapper ) {
        var searchInput = wrapper.querySelector( '.saito-faq-duplicate-search' );
        if ( !searchInput ) {
            return;
        }
        var options = wrapper.querySelectorAll( '.saito-faq-duplicate-target-option' );
        var noResults = wrapper.querySelector( '.saito-faq-duplicate-no-results' );

        searchInput.addEventListener( 'input', function () {
            var query = normalizeForSearch( searchInput.value.trim() );
            var visibleCount = 0;
            options.forEach( function ( label ) {
                var matches = !query || normalizeForSearch( label.textContent ).indexOf( query ) !== -1;
                label.hidden = !matches;
                if ( matches ) {
                    visibleCount++;
                }
            } );
            if ( noResults ) {
                noResults.hidden = visibleCount > 0;
            }
        } );
    } );

    // --- "Dupliquer vers…" : appel AJAX, la sauvegarde est immédiate côté
    // serveur (voir saito_faq_ajax_duplicate(), admin.php) — n'affecte pas
    // le formulaire actuellement ouvert, seulement la destination choisie. ---
    document.querySelectorAll('.saito-faq-duplicate-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrapper = btn.closest('.saito-faq-duplicate');
            var status = wrapper.querySelector('.saito-faq-duplicate-status');
            var checked = wrapper.querySelectorAll('.saito-faq-duplicate-targets input[type="checkbox"]:checked');

            if (!checked.length) {
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
            data.append('action', 'saito_faq_duplicate');
            data.append('nonce', btn.getAttribute('data-nonce'));
            data.append('source', btn.getAttribute('data-source'));
            checked.forEach(function (checkbox) {
                data.append('targets[]', checkbox.value);
            });

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

    // --- Onglets internes du panneau "Navi" (metabox autonome sur la fiche
    // produit, voir includes/saito-panel.php) — indépendant de la boucle par
    // éditeur FAQ ci-dessus : ce panneau peut un jour contenir plusieurs
    // fonctionnalités Navi, pas seulement FAQ. ---
    document.querySelectorAll('.saito-panel-tabs').forEach(function (wrap) {
        var tabs = wrap.querySelectorAll('.saito-panel-tab-btn');

        function activate(tab) {
            tabs.forEach(function (btn) {
                var isActive = btn === tab;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                var panel = document.getElementById(btn.getAttribute('aria-controls'));
                if (panel) {
                    panel.classList.toggle('active', isActive);
                    panel.toggleAttribute('hidden', !isActive);
                }
            });
        }

        wrap.addEventListener('click', function (event) {
            var tab = event.target.closest('.saito-panel-tab-btn');
            if (tab) {
                activate(tab);
            }
        });
    });
})();
