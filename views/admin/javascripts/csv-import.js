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
     * Add a little script that selects the right form values if our spreadsheet
     * uses the same names are our Omeka fields (or similar names like Creator_1,
     * Creator_2, and Creator_3 that should be mapped to our Creator Omeka field)
     */
    Omeka.CsvImport.assistWithMapping = function () {
        jQuery.each(jQuery('select[class="map-element"]'), function() {
            $tr = jQuery(this).parent().parent();
            $label = jQuery($tr).find('strong:eq(0)').text();
            $end = $label.lastIndexOf("_");

            if ($end != -1) {
                $label = $label.substring(0, $end);
            }
            $label = $label.replace(/ /g, '');

            jQuery.each(jQuery($tr).find('option'), function() {
                $optionText = jQuery(this).text().replace(/ /g, '');

                if ($optionText == $label) {
                    jQuery(this).attr('selected', 'selected');
                }
            });
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
        var fieldsCsvReport = $('div.field').has('#elements_are_html');
        var fieldsCsvReportNo = $('div.field').has('#item_type_id, #collection_id, #automap_columns, #items_are_public, #items_are_featured, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        var fieldsItem = $('div.field').has('#item_type_id, #collection_id, #automap_columns, #items_are_public, #items_are_featured, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter, #file_delimiter_name, #file_delimiter');
        var fieldsItemNo = $('div.field').has('#elements_are_html');
        var fieldsFile = $('div.field').has('#automap_columns, #column_delimiter_name, #column_delimiter, #element_delimiter_name, #element_delimiter, #tag_delimiter_name, #tag_delimiter');
        var fieldsFileNo = $('div.field').has('#elements_are_html, #item_type_id, #collection_id, #items_are_public, #items_are_featured, #file_delimiter_name, #file_delimiter');
        if ($('#format-CsvReport').is(':checked')) {
            fieldsCsvReport.slideDown();
            fieldsCsvReportNo.slideUp();
        } else if ($('#format-Item').is(':checked')) {
            fieldsItem.slideDown();
            fieldsItemNo.slideUp();
        } else if ($('#format-File').is(':checked')) {
            fieldsFile.slideDown();
            fieldsFileNo.slideUp();
        } else {
            fieldsCsvReport.slideDown();
            fieldsItem.slideDown();
            fieldsFile.slideDown();
        };
    };

})(jQuery);
