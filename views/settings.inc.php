<?php 	

// Path: views/settings.inc.php

require_once IFLZPO_PLUGIN_PATH . 'google-api-interactions.php';


// if submit button is pressed
if (isset($_POST['submit'])) {

	if ( ! isset( $_POST['settings-submit-nonce'] ) || ! wp_verify_nonce( $_POST['settings-submit-nonce'], 'settings-submit' ) ) {
		
		$errors['nonce-error'] = "Nonce check failed.";
		self::log_action($errors['nonce-error']);
	
	} else {
		// pr("Settings Submitted Successfully");
		// pr($_POST);

		// Update the settings.
		$new_settings = array();
		$new_settings['iflzpo_mm_api_key'] = sanitize_text_field($_POST['iflzpo_mm_api_key']);
		$new_settings['iflzpo_mm_api_secret'] = sanitize_text_field($_POST['iflzpo_mm_api_secret']);
		$new_settings['iflzpo_token_reader_count'] = sanitize_text_field($_POST['iflzpo_token_reader_count']);
		$new_settings['iflzpo_class_registration_active'] = (isset($_POST['iflzpo_class_registration_active'])) ? 1 : 0;
		$new_settings['iflzpo_emergency_contacts_active'] = (isset($_POST['iflzpo_emergency_contacts_active'])) ? 1 : 0;
		$new_settings['iflzpo_referral_program_active'] = (isset($_POST['iflzpo_referral_program_active'])) ? 1 : 0;

		self::update_settings($new_settings);
		
		self::log_action("IFLZPO Settings Updated Successfully");
		
	}
}	

// Class Registration
$iflzpo_class_registration_active = ($this->settings['iflzpo_class_registration_active'] == 1) ? 'checked' : '';

// Referral Program
$iflzpo_referral_program_active = ($this->settings['iflzpo_referral_program_active'] == 1) ? 'checked' : '';

// Emergency Contacts
$iflzpo_emergency_contacts_active = ($this->settings['iflzpo_emergency_contacts_active'] == 1) ? 'checked' : '';

?>

<?php include 'admin-header.inc.php'  ?>

<?php if (isset($errors)) { ?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo $errors['nonce-error']; ?></p>
	</div>
<?php } ?>

<form name="iflzpo_settings" method="post" action="#">
	<?php wp_nonce_field('settings-submit','settings-submit-nonce'); ?>
	
	<h2>MemberMouse Settings</h2>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="iflzpo_mm_api_key">MemberMouse API Key</label></th>
				<td><input name="iflzpo_mm_api_key" id="iflzpo_mm_api_key" value="<?php echo $this->settings['iflzpo_mm_api_key']; ?>" type="text" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="iflzpo_mm_api_secret">MemberMouse API Secret</label></th>
				<td><input name="iflzpo_mm_api_secret" id="iflzpo_mm_api_secret" value="<?php echo $this->settings['iflzpo_mm_api_secret']; ?>" type="password" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="iflzpo_token_reader_count">Token Reader Count</label></th>
				<td><input name="iflzpo_token_reader_count" id="iflzpo_token_reader_count" value="<?php echo $this->settings['iflzpo_token_reader_count']; ?>" type="text" class="regular-text" /></td>
			</tr>
		</tbody>	
	</table>

	<h2>Class Registration Settings</h2>
	
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="iflzpo_class_registration_active">Track Class Registrations</label></th>
				<td><input name="iflzpo_class_registration_active" id="iflzpo_class_registration_active" <?php echo $iflzpo_class_registration_active ?> type="checkbox"  /></td>
			</tr>
		</tbody>	
	</table>

	<h2>Referral Program Settings</h2>
	
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="iflzpo_referral_program_active">Track Referral Program</label></th>
				<td><input name="iflzpo_referral_program_active" id="iflzpo_referral_program_active" <?php echo $iflzpo_referral_program_active ?> type="checkbox"  /></td>
			</tr>
		</tbody>	
	</table>
	
	<h2>Emergency Contact Settings</h2>
	
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="iflzpo_emergency_contacts_active">Track Emergency Contacts</label></th>
				<td><input name="iflzpo_emergency_contacts_active" id="iflzpo_emergency_contacts_active" <?php echo $iflzpo_emergency_contacts_active ?> type="checkbox"  /></td>
			</tr>
		</tbody>	
	</table>
	
	<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	
</form>



<?php 


$d = array( 
	'email' => 'jordan@ideafablabs.com',
	'status_name' => 'Active',
);

try {
	ifl_member_email_update($d);
} catch (Exception $e) {
	pr($e);
}

?>

<?php include 'admin-footer.inc.php'; ?>
