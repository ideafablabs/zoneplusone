<?php

$page_name = 'manage_zone_names_page';

$emptyNameEntered = false;
$newZoneAdded = false;
if (isset($_POST['submit_new_zone_name'])) {
	// If we're adding a new zone
	$newZoneName = trim($_POST['new_zone_name']);
	if ($newZoneName == "") {
		$emptyNameEntered = true;
	} else {
		$this->add_zone_to_zones_table($newZoneName);
		echo "<p style='color:Blue'><b><i>Your new zone '" . $newZoneName . "' was added</i></b></p>";
	}
} else if (isset($_POST['submit_edited_zone_name'])) {
	// Or if we're changing the name of an existing zone
	$selectedZoneId = trim($_POST['selected_zone_id']);
	$editedZoneName = trim($_POST['edited_zone_name']);
	if ($editedZoneName == "") {
		echo "<p style='color:Blue'><b><i>Error - a zone name can't be blank</i></b></p>";
	} else {
		$result = $this->edit_zone_name_in_zones_table($selectedZoneId, $editedZoneName);
		echo "<p style='color:Blue'><b><i>" . $result . "</i></b></p>";
	}
}

global $wpdb;
$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME);

echo "<script>
			function updateTextBox(selection) {
				document.getElementById('edited_zone_name').value=selection.options[selection.selectedIndex].text;
			}</script>";
echo "<h1>Manage Idea Fab Labs zone names</h1>";
echo "<br><h2>To add a new zone, enter its name below, and click 'Add Zone'</h2><form name='form1' method='post' action=''>";
if ($emptyNameEntered) {
	echo "<p style='color: red; font-weight: bold'>Please enter the name for the new zone</p>";
}
echo "<input type='hidden' name='hidden' value='Y'>
		<input type='text' name='new_zone_name'/>
		<input type='submit' name='submit_new_zone_name' value='Add Zone'/>
		<br><br><br><h2>To change the name of an existing zone, select it in the dropdown below, edit its name in the textbox, and click 'Save Name Change'</h2><form name='form1' method='post' action=''>
			<select id='selected_zone_id' name='selected_zone_id' onchange='updateTextBox(this)'>";
for ($i = 0; $i < sizeof($result); $i++) {
	$id = strval($result[$i]->record_id);
	echo "<option value='" . strval($result[$i]->record_id) . "'>" . $result[$i]->zone_name . "</option>";
}
echo "<input type='text' name='edited_zone_name' id='edited_zone_name' value='" . $result[0]->zone_name . "'/>
		<input type='submit' name='submit_edited_zone_name' value='Save Name Change'/>
		</form><br>";


?>

<?php include 'admin-footer.inc.php'; ?>
