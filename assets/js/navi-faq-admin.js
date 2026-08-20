(function () {
    document.querySelectorAll('.navi-faq-editor').forEach(function (editor) {
        var prefix = editor.getAttribute('data-prefix');
        var rows = editor.querySelector('.navi-faq-rows');
        var addBtn = editor.querySelector('.navi-faq-add-row');

        function makeRow() {
            var row = document.createElement('div');
            row.className = 'navi-faq-row';
            row.innerHTML =
                '<p><label>' + naviFaqAdminI18n.group + '</label>' +
                '<input type="text" class="widefat" name="' + prefix + '_group[]" placeholder="' + naviFaqAdminI18n.groupPlaceholder + '" /></p>' +
                '<p><label>' + naviFaqAdminI18n.question + '</label>' +
                '<input type="text" class="widefat" name="' + prefix + '_question[]" /></p>' +
                '<p><label>' + naviFaqAdminI18n.answer + '</label>' +
                '<textarea class="widefat" rows="3" name="' + prefix + '_answer[]"></textarea></p>' +
                '<button type="button" class="button navi-faq-remove-row">' + naviFaqAdminI18n.remove + '</button>';
            return row;
        }

        addBtn.addEventListener('click', function () {
            rows.appendChild(makeRow());
        });

        rows.addEventListener('click', function (event) {
            if (event.target.classList.contains('navi-faq-remove-row')) {
                event.target.closest('.navi-faq-row').remove();
            }
        });
    });
})();
