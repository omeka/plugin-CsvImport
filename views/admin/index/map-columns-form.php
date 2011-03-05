<form id="csvimport" method="post" action="">
<?php
    $colNames = $file->getColumnNames();
    $colExamples = $file->getColumnExamples();
?>
    <table id="csv-import-column-mappings-table" class="simple" cellspacing="0" cellpadding="0">;
	<thead>
	<tr>
        <th>Column</th>
        <th>Example from CSV File</th>
        <th>Map To Element</th>
        <th>Use HTML?</th>
        <th>Tags?</th>
        <th>File?</th>
	</tr>
	</thead>
	<tbody>
	
<?php 
    
for($i = 0; $i < count($colNames); $i++): ?>
        <tr>
        <td><strong><?php echo $colNames[$i]; ?></strong></td>
        <td>&quot;<?php echo $colExamples[$i]; ?>&quot;</td>
        <td>
        <div><?php echo $this->form->getElement(
            CsvImport_Form_Mapping::ELEMENTS_DROPDOWN_PREFIX . $i);
        echo $this->form->getElement(
            CsvImport_Form_Mapping::ELEMENTS_HIDDEN_PREFIX . $i); ?>
        <span id="<?php echo CsvImport_Form_Mapping::ELEMENTS_LIST_PREFIX . $i; ?>"></span>
        </div>
        </td>
        <td><?php echo $this->form->getElement(
            CsvImport_Form_Mapping::HTML_CHECKBOX_PREFIX . $i); ?>
        </td>
        <td><?php echo $this->form->getElement(
            CsvImport_Form_Mapping::TAG_CHECKBOX_PREFIX . $i); ?>
        </td>
        <td><?php echo $this->form->getElement(
            CsvImport_Form_Mapping::FILE_CHECKBOX_PREFIX . $i); ?>
        </td>
        </tr>
<?php endfor; ?>
	</tbody>
	</table>
    <fieldset>
    <?php echo $this->form->submit; ?>
    </fieldset>
</form>
