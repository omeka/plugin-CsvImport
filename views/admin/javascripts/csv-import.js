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
     * Enable/disable options according to selected format.
     */
    Omeka.CsvImport.updateImportOptions = function () {
        var fieldsReport = $('div.field').has('#elements_are_html');
        var fieldsReportNo = $('div.field').has('#item_type_id, #collection_id, #items_are_public, #items_are_featured, #automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        var fieldsItem = $('div.field').has('#item_type_id, #collection_id, #items_are_public, #items_are_featured, #automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        var fieldsItemNo = $('div.field').has('#elements_are_html');
        var fieldsFile = $('div.field').has('#automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter');
        var fieldsFileNo = $('div.field').has('#item_type_id, #collection_id, #items_are_public, #items_are_featured, #elements_are_html, #file_delimiter_name, #file_delimiter');
        var fieldsAll = $('div.field').has('#item_type_id, #collection_id, #items_are_public, #items_are_featured, #elements_are_html, #automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        if ($('#format-Report').is(':checked')) {
            fieldsReport.slideDown();
            fieldsReportNo.slideUp();
        } else if ($('#format-Item').is(':checked')) {
            fieldsItem.slideDown();
            fieldsItemNo.slideUp();
        } else if ($('#format-File').is(':checked')) {
            fieldsFile.slideDown();
            fieldsFileNo.slideUp();
        } else {
            fieldsAll.slideUp();
        };
    };

    /**
     * Enable/disable options after loading.
     */
    Omeka.CsvImport.updateOnLoad = function () {
        Omeka.CsvImport.updateImportOptions();
    };
})(jQuery);
