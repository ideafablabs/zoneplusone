<?php


/**
 * Register a custom menu page.
 */
function ifl_referral_dash_reg_menu_page() {
    add_menu_page(
        __( 'Member Referrals', 'textdomain' ),
        'Member Referrals',
        'edit_posts',
        'member-referrals-registration',
        'ifl_referral_dash',
        '',
        6
    );
}
add_action( 'admin_menu', 'ifl_referral_dash_reg_menu_page' );

function ifl_referral_dash() { 	
	
	$members = mm_get_members_with_referrals();
	//pr($members);
	echo '<p>Referral instructions go here...</p>';
	echo '<table class="member_select_list list-group filterable"><thead><tr class="member-table-head">
		<th>Member First Name</th>
		<th>Member Last Name</th>
		<th>Referred By</th>
		<th>Redeemed</th>
		</tr></thead><tbody>';

	foreach ($members as $member) {
		if (empty($member->referring_member)) continue;
		//pr($member);
		
		$mm_link = 'https://santacruz.ideafablabs.com/wp-admin/admin.php?page=manage_members&module=details_custom_fields&user_id='.$member->id;
		
		echo '<tr>';
		echo '<td>'.$member->first_name.'</td>';
		echo '<td>'.$member->last_name.'</td>';
		echo '<td>'.$member->referring_member.'</td>';
		echo '<td>'.$member->referral_redeemed.' - <a href="'.$mm_link.'">Link</a></td>';
		echo '</tr>';		
	}
	echo '</tbody></table>';		 

}


// ==== MemberMouse Get All Active Members With Referral Custom Fields 
function mm_get_members_with_referrals() {
	global $wpdb;

	// MM User Status IDs
	//	1 = Active
	// 2 = cancelled
	// 5 = Overdue
	// 4 = Paused
	// 9 = Pending Cancellation

	$sql = "SELECT 
		mm_user_data.wp_user_id AS 'id',
		mm_user_data.first_name, 
		mm_user_data.last_name,
		cf_data_1.value AS 'referral_redeemed',
    	cf_data_2.value AS 'referring_member'
	FROM 
		mm_user_data
	LEFT JOIN
		mm_custom_field_data AS cf_data_1 ON mm_user_data.wp_user_id = cf_data_1.user_id AND cf_data_1.custom_field_id = 5
	LEFT JOIN
    	mm_custom_field_data AS cf_data_2 ON mm_user_data.wp_user_id = cf_data_2.user_id AND cf_data_2.custom_field_id = 4
	WHERE 
		mm_user_data.status IN (1, 5, 9)";
		
	$result = $wpdb->get_results($sql);

	// pr($result);

	return (array) $result;
}
?>