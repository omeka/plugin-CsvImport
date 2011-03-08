if (typeof Omeka === 'undefined') {
    Omeka = {};
}

Omeka.CsvImport = {};

/**
 * Allow multiple mappings for each field, and add buttons to allow a mapping
 * to be removed.
 */
Omeka.CsvImport.enableElementMapping = function () {
    jQuery('form#csvimport .map-element').change(function () {
        var select = jQuery(this);
        var addButton = select.siblings('span.add-element');
        if (!addButton.length) {
            var addButton = jQuery('<span class="add-element"></span>');
            addButton.click(function() {
                var copy = select.clone(true);
                select.after(copy);
                jQuery(this).remove();
            });
            select.after(addButton);
        };
    });
};

/**
 * Add a confirm step before undoing an import.
 */
Omeka.CsvImport.confirmUndoImport = function () {
    jQuery('.csv-undo-import').click(function () {
        return confirm("Undoing an import will delete all of its imported items. Are you sure you want to undo this import?");
    });
};
