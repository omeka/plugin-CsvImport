if (typeof Omeka === 'undefined') {
    Omeka = {};
}

Omeka.CsvImport = {};

/**
 * Allow multiple mappings for each field, and add buttons to allow a mapping
 * to be removed.
 */
Omeka.CsvImport.enableElementMapping = function () {
    jQuery('.csv-import-element-select').change(function () {
        var select = jQuery(this);
        var elementId = select.val();
        if (elementId === '') {
            return;
        }
        var elementName = select.find(':selected').text();

        var hiddenInput = select.siblings('input[type="hidden"]');
        var mappingsString = hiddenInput.val();
        var mappings = [];
        if(mappingsString) {
            mappings = hiddenInput.val().split(',');
        }
        if (jQuery.inArray(elementId, mappings) === -1) {
            mappings.push(elementId);
            hiddenInput.val(mappings.join(','));

            var newMapping = jQuery('<li class="csv-import-element-delete">' + elementName + '</li>');
            newMapping.click(function () {
                Omeka.CsvImport.removeElementMapping(elementId, this);
            });

            var listSpan = select.siblings('span');
            var list = listSpan.children('ul');
            if (!list.length) {
                list = jQuery('<ul></ul>').appendTo(listSpan);
            }

            list.append(newMapping);
        }
        select.val('');
    });
};
/**
 * Remove a mapping and its associated button.
 *
 * @param {string} [elementId] ID of the element to remove the mapping to.
 * @param {Element} [removeButton] Button for mapping to remove.
 */
Omeka.CsvImport.removeElementMapping = function(elementId, removeButton) {
    var button = jQuery(removeButton);
    var hiddenInput = button.parent().parent().siblings('input[type="hidden"]');
    var mappings = hiddenInput.val().split(',');
    var index = jQuery.inArray(elementId, mappings);
    if (index !== -1) {
        mappings.splice(index, 1);
    }
    hiddenInput.val(mappings.join(','));

    button.remove();
};

/**
 * Add a confirm step before undoing an import.
 */
Omeka.CsvImport.confirmUndoImport = function () {
    jQuery('.csv-undo-import').click(function () {
        return confirm("Undoing an import will delete all of its imported items. Are you sure you want to undo this import?");
    });
};
