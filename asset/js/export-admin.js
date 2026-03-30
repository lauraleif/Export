(function ($) {
    $(document).ready(function () {
        const resourcesCheckboxes = $("input[type='checkbox'][name='resource_ids[]']");
        const exportButtonForm = $('#export-button-form');
        const selectAllCheckbox = $("input[type='checkbox'].select-all");
        const selectedIds = new Set();

        selectAllCheckbox.on('change', function () {
            resourcesCheckboxes.each(function () {
                const value = $(this).val();
                if ($(this).is(':checked') && !selectedIds.has(value)) {
                    selectedIds.add(value);
                }
                if ($(this).is(':checked') === false && selectedIds.has(value)) {
                    selectedIds.delete(value);
                }
            });
            updateHiddenInputs();
        });

        resourcesCheckboxes.on('change', function () {
            const value = $(this).val();
                if ($(this).is(':checked') && !selectedIds.has(value)) {
                    selectedIds.add(value);
                }
                if ($(this).is(':checked') === false && selectedIds.has(value)) {
                    selectedIds.delete(value);
                }
            updateHiddenInputs();
        });

        function updateHiddenInputs() {
            exportButtonForm.find('input[name="resource_ids[]"]').remove();
            selectedIds.forEach(function(id) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'resource_ids[]',
                    value: id
                }).appendTo(exportButtonForm);
            });
        }
    });
})(jQuery);