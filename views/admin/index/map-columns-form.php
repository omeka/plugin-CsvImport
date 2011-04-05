<form id="csvimport" method="post" action="">
<?php
    $colNames = $this->columnNames;
    $colExamples = $this->columnExamples;
?>
    <table id="column-mappings" class="simple" cellspacing="0" cellpadding="0">
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
        <td><strong><?php echo html_escape($colNames[$i]); ?></strong></td>
        <td>&quot;<?php echo html_escape($colExamples[$colNames[$i]]); ?>&quot;</td>
        <?php echo $this->form->getSubForm("row$i"); ?>
        </tr>
<?php endfor; ?>
    </tbody>
    </table>
    <fieldset>
    <?php echo $this->form->submit; ?>
    </fieldset>
</form>
