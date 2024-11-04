<?php 	
	
	if (isset($_POST['submit'])) {

		if ( ! isset( $_POST['settings-submit-nonce'] ) || ! wp_verify_nonce( $_POST['settings-submit-nonce'], 'settings-submit' ) ) {
			
			$errors['nonce-error'] = "Nonce check failed.";
			self::log_action($errors['nonce-error']);
		
		} else {

			// $update_token_reader_count = $_POST['token_reader_count'];  
			// update_option('iflpm_token_reader_count', $update_token_reader_count);
			
			self::log_action("IFLZPO Settings Updated Successfully");
		}
	}	

	?>
	
<?php include 'admin-header.inc.php'  ?>

<h2>Sunset Images</h2>
<?php 

if (file_exists(IFLZPO_SUNSET_IMG_FILE)) {
    
	// get an array of all of the lines in the file (which are json) and display a table of them with links to the images:
	$lines = file(IFLZPO_SUNSET_IMG_FILE);
	$lines = array_reverse($lines);

	$table = '<table>';
	$table .= '<tr><th>Date</th><th>Time</th><th>Image</th><th>Prompt</th></tr>';
	foreach ($lines as $line) {
		$line = json_decode($line, true);
		$table .= '<tr>';
		$table .= '<td>' . $line['date'] . '</td>';
		$table .= '<td>' . $line['time'] . '</td>';
		$table .= '<td><a href="' . $line['imageUrl'] . '" target="_blank"><img src="' . $line['imageUrl'] . '" width="100" height="100"></a></td>';	
		$table .= '<td>' . $line['imagePrompt'] . '</td>';
		$table .= '</tr>';
	}
	$table .= '</table>';
	echo $table;
	

    // $lines = file(IFLZPO_SUNSET_IMG_FILE);
	// $lastLine = end($lines);
    // $lastLine = json_decode($lastLine, true);
    // $lastDate = $lastLine['date'];
    
    // $lastImageUrl = $lastLine['imageUrl'];
    // $lastImagePrompt = $lastLine['imagePrompt'];
    // $lastTime = $lastLine['time'];

}

$zonedata = $this->get_zone_plus_ones_array_for_dashboard();
		// pr($zonedata);

		echo '<table class="zonedata">
				<tr>
					<th>Zone</th>
					<th>Total</th>
					<th>Monthly</th>
				</tr>';

		foreach ($zonedata as $key => $value) {
			echo '<tr>';
			echo "<td>" . $value['zone_name'] . "</td>";
			echo "<td>" . $value['this_month_plus_one_count'] . "</td>";
			echo "<td>" . $value['total_plus_one_count'] . "</td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "</br></br>TESTING!</br>";

		pr(self::get_list_of_active_membermouse_users());
		// $this->test_zone_tokens_table_stuff();
		// $this->test_zones_table_stuff();
		// $this->test_plus_one_zones_table_stuff();

		// echo "</br>" . $this->get_zone_token_ids_by_user_id("3") . "</br>";
		// echo "</br>" . $this->get_user_id_from_zone_token_id("1") . "</br>";

		// Tests
		$response = $this->add_zone_token_to_zone_tokens_table("1", "3");
		if (is_wp_error($response)) {
			errout($response->get_error_messages());
		} else {
			pr($response);
		}

		// echo "</br>" . $this->add_zone_to_zones_table("Electronics zone") . "</br>";

//        echo "Testing zone token deletion\n";
//        $response = $this->delete_zone_token("15796", "79");
//        if (is_wp_error($response)) {
//            pr("Error - " . $response->get_error_messages()[0]);
//            errout($response->get_error_messages());
//        } else {
//            pr($response);
//        }
 ?>

<?php include 'admin-footer.inc.php'; ?>
