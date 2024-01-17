<?php


/**
 * Register a custom menu page.
 */
function ifl_register_class_reg_menu_page() {
    add_menu_page(
        __( 'Class Registration', 'textdomain' ),
        'Class Registration',
        'edit_posts',
        'class-registration',
        'ifl_test_function',
        '',
        6
    );
}
add_action( 'admin_menu', 'ifl_register_class_reg_menu_page' );

function ifl_test_function() { 	
	
	if (isset($_POST['update_class_name'])) {		
		
		if ( ! isset( $_POST['class-select-nonce'] ) || ! wp_verify_nonce( $_POST['class-select-nonce'], 'class-select' ) ) {
   		
   		$errors['nonce-error'] = "Nonce check failed.";
   			
        } else {
   		
			$update_selected_class_name = $_POST['update_class_name'];
			$instructor_email_address = $_POST['instructor_email_address'];
			update_option('ifl_selected_class_name', $update_selected_class_name);
			
		}

	} else if (isset($_POST['submit'])) {
		///Needs Nonce
	}	

	$selected_class_name = get_option('ifl_selected_class_name');

	
	$form_id = '27';
	$class_names = array();
	
	$paging = array('offset' => 0, 'page_size' => 100);
	$entries = GFAPI::get_entries($form_id, $paging);

?>
	<div class="wrap">
	<p>		
	<form name="select_event" method="post" action="#">
    <input type='hidden' name='instructor_email_address' value='<?php echo $instructor_email_address;?>'/>

	<?php wp_nonce_field('class-select','class-select-nonce'); ?>
		<select id="selected_class_name" name="update_class_name" onchange="this.form.submit()">
	<?php

	foreach($entries as $key => $entry) {
		$class_name = $entry[15];
		if (!in_array($class_name,$class_names)) {
			array_push($class_names,$class_name);

			$selected = ($selected_class_name == $class_name) ? 'selected = "selected"' : '';
			echo '<option value="'.$class_name.'" '.$selected.'>'.$class_name.'</option>';
		}
	}
	echo '</select></form></p>';
	// pr($res);
	//pr($class_names);
	
	$search_criteria = array(
		'status'        => 'active',
		'field_filters' => array(
			'mode' => 'any',
			array(
				'key'   => '15',
				'value' => $selected_class_name
			)
		)
	);

	// Getting the entries
	$result = GFAPI::get_entries( $form_id, $search_criteria );

	$studentEmailAddresses = array();

	echo '<table class="wp-list-table widefat fixed striped table-view-list"><thead><tr><td>Date Created</td><td>Name</td><td>Email</td><td>Entry Link</td><td>Transaction Link</td></tr></thead><tbody>';
	foreach($result as $key => $entry) {
		echo '<tr><td>'.$entry['date_created'].'</td><td>'.$entry['1.3'].' '. $entry['1.6'] . '</td><td><a href="mailto:'.$entry[2].'">'.$entry[2].'</a></td><td><a href="/wp-admin/admin.php?page=gf_entries&view=entry&lid='.$entry['id'].'&id='.$entry['form_id'].'" target="_blank">Entry #'.$entry['id'].'</a></td><td><a href="https://dashboard.stripe.com/payments/'.$entry['transaction_id'].'" target="_blank">Tr Link</a><td> </tr>';
		array_push($studentEmailAddresses, $entry[2]);
		$instructor_email_address = $entry['23'];
		
		// //wp-admin/admin.php?page=gf_entries&view=entry&id='.$entry['id'].'&lid='.$entry['lid']
		
		//pr($entry);
	}
	echo "</tbody></table>";
	
	// echo "<p><code>".implode(',', $studentEmailAddresses)."</code></p>";
    // construct mailto with instructor email address (if entered) and to bcc all students
	//if ($instructor_email_address != "") {
		$mailtoAllStr = "mailto:" . $instructor_email_address . "?bcc=" . implode(';', $studentEmailAddresses);
    //} else {
	//	$mailtoAllStr = "mailto:?bcc=" . implode(';', $studentEmailAddresses);
    //}
	// Button to email the instructor (if email address entered) and BCC all students
	echo "<p><a href=" . $mailtoAllStr . "><button id='emailAll'>Email all</button></a></p>";

	?>
			</div> 
	<?php
	
	/*
	[status] => active
            [15] => test
            [1.3] => Jordan
            [1.6] => Layman
            [2] => santacruz@ideafablabs.com
            [22.1] => active
            [10.1] => Price
            [10.2] => 160
            [10.3] => 1
	*/
}


/**
 * Shortcode wrapper for event registration form.
 * @shortcode definition
 * Usage: [classregister event="Event Title Goes Here" instructor="example@gmail.com" memberpricing="active" memberprice="45" price="60" capacity="8" ]
 */
add_shortcode( 'classregister', 'ifl_classregistration_wrapper' );
function ifl_classregistration_wrapper($atts) {
	$args = shortcode_atts(array(
		'regform' => 27,
		'class' => '',
		'instructor' => '',
		'price' => '',
		'memberpricing' => 'inactive',
		'memberprice' => '',		
		'capacity' => '0',
		'classFieldID' => '15'		
	), $atts);
	$response = "";

	$regform = $args['regform'];
	$class = $args['class'];
	$classFieldID = $args['classFieldID'];
	$capacity = $args['capacity'];
	$instructor = $args['instructor'];
	$classFull = 0;

	// get entries from this form where $class is the same and check entry count. 
	// if price = '' then show there is supposed to be a form here but ...;
	// if cap is met, show class is full...
	
	
	if ($capacity > 0) {
		$search_criteria = array(
			'field_filters' => array(
			    array(
		            'key'   => '15',
		            'value' => $class
		        ),
		    )
		);
		
		$entry_count = GFAPI::count_entries($regform,$search_criteria);

		/// need to add a check for something like "enrolled == 1" to filter people who unenrolled.
		
		if ($entry_count >= $capacity) {
			$classFull = 1;
		}

	}

	//$class = (isset($args['event_id'])) ? $_REQUEST['event_id'] : $args['event_id'];
	
	$submit = (isset($_REQUEST['submit'])) ? $_REQUEST['submit'] : '0';
			
	// pr($attendee_count);

	// Begin response html string.
	// $response .= '<div class="registration container">';


	// Complete with Entry GForm and go back to Entry List or Create New User again.
	if ($submit) {
		// $response .= "<p>Submitted</p>";
		// pr($_REQUEST['submit']); 
	} else {

		/// we want to also check if the registration entry has a 'cancelled' flag on it...
		
		$response .= '<p><strong>Seats remaining: '.($capacity - $entry_count).'/'.$capacity.'</strong></p>';
		
		if ($classFull) {
			$response .= '<p class="notice">Sorry this class is full...</p>';
		} else {

			$field_values = array(
				'class' => $args['class'], 
				'price' => $args['price'], 
				'memberprice' => $args['memberprice'], 
				'memberpricing' => $args['memberpricing'], 
				'instructor' => $args['instructor'], 
			);

			//[gravityform id="27" title="true" description="true" ajax="true" field_values='class=Introduction to Wood Shop&amp;price=160&amp;memberprice=140&amp;memberpricing=active&amp;instructor=altavistaforest@gmail.com']
			
			// class=Introduction to Wood Shop&amp;
			// price=160
			// memberprice=140
			// memberpricing=active
			// instructor=altavistaforest@gmail.com


			//gravity_form( $id_or_title, $display_title = true, $display_description = true, $display_inactive = false, $field_values = null, $ajax = false, $tabindex, $echo = true );
			$response .= '<div class="heh">';
			$response .= gravity_form( $regform, true, true, false, $field_values, true, 1, false);
			$response .= "</div>";
		}

	}

	return $response;
}

?>