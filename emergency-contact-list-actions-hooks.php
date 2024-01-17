<?php

/**
 * Register a custom menu page.
 */
function ifl_emergency_dash_reg_menu_page() {
    add_menu_page(
        __( 'Emergency Contacts', 'textdomain' ),
        'Emergency Contacts',
        'edit_posts',
        'member-emergency-contacts-registration',
        'ifl_emergency_contact_dash',
        '',
        6
    );
}
add_action( 'admin_menu', 'ifl_emergency_dash_reg_menu_page' );

function ifl_emergency_contact_dash() { 	
	
	$members = mm_get_members_with_econtacts();
	//pr($members);
	
	echo '<p>Emergency Contact instructions go here...</p>';
	echo '<div class="table-filter"><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button class="clear-filter" onclick="document.getElementById(\'q\').value = \'\';$(\'.table-filter #q\').focus();">Clear</button></div>';
	echo '<table class="member_select_list list-group filterable"><thead><tr class="member-table-head">
		<th>Member First Name</th>
		<th>Member Last Name</th>
		<th>Emergency Contact</th>
		<th>Phone</th>
		</tr></thead><tbody>';

	foreach ($members as $member) {
		//if (empty($member->referring_member)) continue;
		//pr($member);
		
		$mm_link = 'https://santacruz.ideafablabs.com/wp-admin/admin.php?page=manage_members&module=details_custom_fields&user_id='.$member->id;
		
		echo '<tr class="filter-item">';
		echo '<td>'.$member->first_name.'</td>';
		echo '<td>'.$member->last_name.'</td>';
		echo '<td>'.$member->econtact_name.'</td>';
		echo '<td>'.$member->econtact_phone.' - <a href="'.$mm_link.'">Link</a></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';		 

}


// ==== MemberMouse Get All Active Members With Referral Custom Fields 
function mm_get_members_with_econtacts() {
	global $wpdb;

	// MM User Status IDs
	// 1 = Active
	// 2 = Cancelled
	// 5 = Overdue
	// 4 = Paused
	// 9 = Pending Cancellation

	$sql = "SELECT 
		mm_user_data.wp_user_id AS 'id',
		mm_user_data.first_name, 
		mm_user_data.last_name,
		cf_data_1.value AS 'econtact_name',
    	cf_data_2.value AS 'econtact_phone'
	FROM 
		mm_user_data
	LEFT JOIN
		mm_custom_field_data AS cf_data_1 ON mm_user_data.wp_user_id = cf_data_1.user_id AND cf_data_1.custom_field_id = 12
	LEFT JOIN
    	mm_custom_field_data AS cf_data_2 ON mm_user_data.wp_user_id = cf_data_2.user_id AND cf_data_2.custom_field_id = 13";
	
//	WHERE 
	//	mm_user_data.status IN (1, 5, 9)";
		
	$result = $wpdb->get_results($sql);

	// pr($result);

	return (array) $result;
}
?>