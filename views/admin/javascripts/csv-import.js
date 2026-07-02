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
    //TODO: De-integrate from CVI // old CH addition
        /** Connie  - Map our elements **/
	$('#row4-tags').prop('checked',true);
    $('#row0-file').prop('checked',true);
    $('#row51-cats').prop('checked',true); //Source Repository 
    $('#row52-geochron').prop('checked',true); //Source Repository 
    $('#row54-contribution').prop('checked',true); //Contributed ID
    $('#row5-html').prop('checked',true); //description
    $('#row7-html').prop('checked',true); //Abstract
    $('#row9-html').prop('checked',true); //source
    $('#row15-html').prop('checked',true); //Requires
    $('#row16-html').prop('checked',true); //IsPartOf 
    $('#row17-html').prop('checked',true); //Relation.IsReferencedBy
    $('#row20-html').prop('checked',true); //Relation.IsFormatOf
    $('#row25-html').prop('checked',true); //Publisher
    $('#row26-html').prop('checked',true); //Contributor 
    $('#row40-html').prop('checked',true); //Provenance
    $('#row41-html').prop('checked',true); //Rights Holder
    $('#row44-html').prop('checked',true); //Contributing Project
    $('#row46-html').prop('checked',true); //Digital Collection
    $('#row47-html').prop('checked',true); //Source Repository
    $('#row48-html').prop('checked',true); //External Reference
    $('#row49-html').prop('checked',true); //External Title
    //END CH additions
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
        var fields = $('div.field').has('#automap_columns_names_to_elements, #item_type_id, #collection_id, #items_are_public, #items_are_featured, #column_delimiter, #element_delimiter, #tag_delimiter, #file_delimiter');
        if ($('#omeka_csv_export').is(':checked')) {
          fields.slideUp();
        } else {
          fields.slideDown();
        }
    };
         
})(jQuery);
