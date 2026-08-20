(function () {
    document.querySelectorAll('.navi-faq-editor').forEach(function (editor) {
        var prefix = editor.getAttribute('data-prefix');
        var emptyLabel = editor.getAttribute('data-empty-label') || '';
        var rows = editor.querySelector('.navi-faq-rows');
        var addBtn = editor.querySelector('.navi-faq-add-row');

        function renumber() {
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

        function makeRow() {
            var row = document.createElement('div');
            row.className = 'navi-faq-row';
            row.innerHTML =
                '<div class="navi-faq-row-header">' +
                    '<span class="navi-faq-row-title"></span>' +
                    '<button type="button" class="navi-faq-remove-row" aria-label="' + naviFaqAdminI18n.remove + '">&times;</button>' +
                '</div>' +
                '<div class="navi-faq-row-body">' +
                    '<p class="navi-faq-field navi-faq-field-group">' +
                        '<label>' + naviFaqAdminI18n.group + '</label>' +
                        '<input type="text" class="widefat" list="navi-faq-themes-datalist" name="' + prefix + '_group[]" placeholder="' + naviFaqAdminI18n.groupPlaceholder + '" />' +
                    '</p>' +
                    '<p class="navi-faq-field">' +
                        '<label>' + naviFaqAdminI18n.question + '</label>' +
                        '<input type="text" class="widefat" name="' + prefix + '_question[]" />' +
                    '</p>' +
                    '<p class="navi-faq-field">' +
                        '<label>' + naviFaqAdminI18n.answer + '</label>' +
                        '<textarea class="widefat" rows="3" name="' + prefix + '_answer[]"></textarea>' +
                    '</p>' +
                '</div>';
            return row;
        }

        addBtn.addEventListener('click', function () {
            var empty = rows.querySelector('.navi-faq-empty');
            if (empty) {
                empty.remove();
            }
            var row = makeRow();
            rows.appendChild(row);
            renumber();
            var questionField = row.querySelector('input[name$="_question[]"]');
            if (questionField) {
                questionField.focus();
            }
        });

        rows.addEventListener('click', function (event) {
            if (event.target.classList.contains('navi-faq-remove-row')) {
                event.target.closest('.navi-faq-row').remove();
                renumber();
            }
        });

        renumber();
    });
})();
