if (typeof Omeka === 'undefined') {
    Omeka = {};
}

Omeka.CsvImport = {};

/**
 * Allow multiple mappings for each field, and add buttons to allow a mapping
 * to be removed.
 */
Omeka.CsvImport.enableElementMapping = function() {(function ($) {
    $('form#csvimport .map-element').change(function () {
        var select = $(this);
        var addButton = select.siblings('span.add-element');
        if (!addButton.length) {
            var addButton = $('<span class="add-element"></span>');
            addButton.click(function() {
                var copy = select.clone(true);
                select.after(copy);
                $(this).remove();
            });
            select.after(addButton);
        };
    });
})(jQuery)};

/**
 * Add a confirm step before undoing an import.
 */
Omeka.CsvImport.confirmUndoImport = function() {(function ($) {
    $('.csv-undo-import').click(function () {
        return confirm("Undoing an import will delete all of its imported items. Are you sure you want to undo this import?");
    });
})(jQuery)};
