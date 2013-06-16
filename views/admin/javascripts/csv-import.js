if (!Omeka) {
    var Omeka = {};
}

Omeka.CsvImport = {};

(function ($) {
    /**
     * Allow multiple mappings for each field, and add buttons to allow a mapping
     * to be removed.
     */
    Omeka.CsvImport.enableElementMapping = function () {
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
    };

    /**
     * Add a confirm step before undoing an import.
     */
    Omeka.CsvImport.confirm = function () {
        $('.csv-undo-import').click(function () {
            return confirm("Undoing an import will delete all of its imported items. Are you sure you want to undo this import?");
        });
    };

    /**
     * Disable most options if Import from Csv Report is checked
     */
    Omeka.CsvImport.updateImportOptions = function () {
        // we need to test whether the checkbox is checked
        // because fields will all be displayed if the form fails validation
        var fieldsAll = $('div.field').has('#automap_columns_names_to_elements, #item_type_id, #collection_id, #items_are_public, #items_are_featured, #elements_are_html, #automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        if ($('#omeka_csv_export').is(':checked')) {
          fields.slideUp();
        } else {
          fields.slideDown();
        }
    };

    /**
     * Enable/disable column delimiter field.
     */
    Omeka.CsvImport.updateColumnDelimiterField = function () {
        var fieldSelect = $('#column_delimiter_name');
        var fieldCustom = $('#column_delimiter');
        if (fieldSelect.val() == 'custom') {
            fieldCustom.show();
        } else {
            fieldCustom.hide();
        };
    };

    /**
     * Enable/disable element delimiter field.
     */
    Omeka.CsvImport.updateElementDelimiterField = function () {
        var fieldSelect = $('#element_delimiter_name');
        var fieldCustom = $('#element_delimiter');
        if (fieldSelect.val() == 'custom') {
            fieldCustom.show();
        } else {
            fieldCustom.hide();
        };
    };

    /**
     * Enable/disable tag delimiter field.
     */
    Omeka.CsvImport.updateTagDelimiterField = function () {
        var fieldSelect = $('#tag_delimiter_name');
        var fieldCustom = $('#tag_delimiter');
        if (fieldSelect.val() == 'custom') {
            fieldCustom.show();
        } else {
            fieldCustom.hide();
        };
    };

    /**
     * Enable/disable file delimiter field.
     */
    Omeka.CsvImport.updateFileDelimiterField = function () {
        var fieldSelect = $('#file_delimiter_name');
        var fieldCustom = $('#file_delimiter');
        if (fieldSelect.val() == 'custom') {
            fieldCustom.show();
        } else {
            fieldCustom.hide();
        };
    };

    /**
     * Enable/disable options after loading.
     */
    Omeka.CsvImport.updateOnLoad = function () {
        Omeka.CsvImport.updateImportOptions();
        Omeka.CsvImport.updateColumnDelimiterField();
        Omeka.CsvImport.updateElementDelimiterField();
        Omeka.CsvImport.updateTagDelimiterField();
        Omeka.CsvImport.updateFileDelimiterField();
    };
})(jQuery);
