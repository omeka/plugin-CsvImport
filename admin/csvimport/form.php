<?php head(); ?>

<div id="primary">

<h2>Import from CSV to Omeka: Step 1</h2>

<form action="<?php echo uri('csvimport/index/mapping'); ?>" method="post">
<p>Select a CSV file to import into Omeka:</p>
<p><select name="file">
    <option value="">[select a file]</option>
    <?php foreach ($files as $file): ?>
    <option value="<?php echo htmlentities($file) ?>"><?php echo htmlentities($file) ?></option>
    <?php endforeach; ?>
</select></p>
<p>Select an Omeka item type that corresponds to the imported data:</p>
<p><select name="typeId">
    <option value="">[select an item type]</option>
    <?php foreach ($types as $type): ?>
    <option value="<?php echo htmlentities($type['id']) ?>"><?php echo htmlentities($type['name']) ?></option>
    <?php endforeach; ?>
</select></p>
<p><input type="submit" value="contine to the next step" /></p>
</form>

</div>

<?php foot(); ?>