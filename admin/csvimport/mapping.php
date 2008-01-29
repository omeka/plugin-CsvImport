<?php head(); ?>
<style>
table#csvimport-mappingTable, table#csvimport-mappingTable tr  {
    /*border:none;*/
}
</style>
<div id="primary">

<h2>Import from CSV to Omeka: Step 2</h2>

<p>Map the CSV headers from "<?php echo $file; ?>" to the corresponding "<?php echo $typeName; ?>" fields:</p>

<form action="<?php echo uri('csvimport/index/import'); ?>" method="post">
<table id="csvimport-mappingTable">
    <tr>
        <th>CSV Headers</th>
        <th>Import to Field</th>
    </tr>
<?php foreach ($csvHeaders as $key => $csvHeader): ?>
    <tr>
        <td><?php echo htmlentities($csvHeader); ?></td>
        <td><select name="fields[<?php echo $key; ?>]">
            <option value="">[do not import]</option>
            <?php foreach ($fields as $field): ?>
            <option value="<?php echo isset($field['id']) ? $field['id'] : htmlentities($field['name']); ?>"><?php echo htmlentities($field['name']); ?></option>
            <?php endforeach; ?>
        </select></td>
    </tr>
<?php endforeach; ?>
</table>
<input type="hidden" name="file" value="<?php echo $file; ?>" />
<input type="hidden" name="typeId" value="<?php echo $typeId; ?>" />
<input type="submit" value="click here to begin the import" />
</form>

</div>

<?php foot(); ?>