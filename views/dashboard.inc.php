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


<?php 

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
