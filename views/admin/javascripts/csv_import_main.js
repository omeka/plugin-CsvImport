function csvImportAddElementToColumnMap(elementsListDivId, elementsDropDownId, elementsHiddenInputId) {
    // element list div
    var eListDiv = $(elementsListDivId);
    
    // elements list
    var eList = eListDiv.firstChild;
    if (!eList) {
        eList = csvImportCreateNode('ul', '');  
        eListDiv.appendChild(eList);
    }
    
    // selected element id
    var sElementId = $(elementsDropDownId).value;
    
    // new element 
    var nElement = csvImportCreateNode('li', csvImportGetLabelFromDropDown(elementsDropDownId));
    nElement.setAttribute('class', 'csv-import-element-delete');    
    nElement.setAttribute('onclick', 'csvImportRemoveElementFromColumnMap(' + "'" + sElementId + "'" + ',' + "'" + elementsListDivId + "'"  + ',' + "'" + elementsHiddenInputId + "'" + ',this);');
    
    // if the element is new, add it to the elements list
    if (!csvImportColumnMapHasElement(sElementId, elementsHiddenInputId)) {
        eList.appendChild(nElement);
        if ($(elementsHiddenInputId).value != '') {
            $(elementsHiddenInputId).value += ',';
        }
        $(elementsHiddenInputId).value += sElementId;
    }
}

function csvImportRemoveElementFromColumnMap(elementId, elementsListDivId, elementsHiddenInputId, elementListItem) {
    // element list div
    var eListDiv = $(elementsListDivId);
    
    // elements list
    var eList = eListDiv.firstChild;
    
    // remove the element from the list
    eList.removeChild(elementListItem);
    
    // remove the list if it is empty
    if (!eList.firstChild) {
        eListDiv.removeChild(eList);
    }
    
    
    // rebuild the hidden text without the element
    var elementIds = $(elementsHiddenInputId).value.split(',');
    var hT = '';
    for(var i = 0; i < elementIds.length; i++) {
        if (elementIds[i] != elementId && elementIds[i] != '') {
            if (hT != '') {
                hT += ','
            }
            hT += elementIds[i];
        }
    }
    $(elementsHiddenInputId).value = hT;
}

function csvImportColumnMapHasElement(elementId, elementsHiddenInputId) {
    var elementIds = $(elementsHiddenInputId).value.split(',');
    for(var i = 0; i < elementIds.length; i++) {
        if (elementIds[i] == elementId) {
            return true;
        }
    }
    return false;
}

function csvImportGetLabelFromDropDown(dropDownId) {
    return $(dropDownId)[$(dropDownId).selectedIndex].innerHTML;
}

function csvImportCreateNode(nodeType, nodeText ) {
    var n = document.createElement(nodeType);
    if (nodeText != '') {
        var nT = document.createTextNode(nodeText);
        n.appendChild(nT);
    }
    return n;
}

function csvConfirmUndoImport() {
    if (confirm("Undoing an import will delete all of its imported items. Are you sure you want to undo this import?")) {
        return confirm('If you undo this import, your imported items for this import will be deleted.  Are you really sure you want to undo this import?');
    }
    return false;
}