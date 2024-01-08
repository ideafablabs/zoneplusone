<?php 	
	
if (isset($_POST['submit'])) {

	if ( ! isset( $_POST['settings-submit-nonce'] ) || ! wp_verify_nonce( $_POST['settings-submit-nonce'], 'settings-submit' ) ) {
		
		$errors['nonce-error'] = "Nonce check failed.";
		self::log_action($errors['nonce-error']);
	
	} else {

		pr("Settings Submitted Successfully");
		pr($_POST);

		$update_iflzpo_token_reader_count = $_POST['iflzpo_token_reader_count'];  
		update_option('iflzpo_token_reader_count', $update_iflzpo_token_reader_count);

		$update_iflzpo_mm_api_key = sanitize_text_field($_POST['iflzpo_mm_api_key']);  
		update_option('iflzpo_mm_api_key', $update_iflzpo_mm_api_key);
		
		$update_iflzpo_mm_api_secret = sanitize_text_field($_POST['iflzpo_mm_api_secret']);  
		update_option('iflzpo_mm_api_secret', $update_iflzpo_mm_api_secret);

		self::log_action("IFLZPO Settings Updated Successfully");
	}
}	


$iflzpo_mm_api_key = get_option('iflzpo_mm_api_key');
$iflzpo_mm_api_secret = get_option('iflzpo_mm_api_secret');

$iflzpo_token_reader_count = get_option('ifzpo_token_reader_count');
?>

<?php include 'admin-header.inc.php'  ?>

<h2>MemberMouse Settings</h2>
<?php if (isset($errors)) { ?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo $errors['nonce-error']; ?></p>
	</div>
<?php } ?>

<form name="iflzpo_settings" method="post" action="#">
	<?php wp_nonce_field('settings-submit','settings-submit-nonce'); ?>
	
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="iflzpo_mm_api_key">MemberMouse API Key</label></th>
				<td><input name="iflzpo_mm_api_key" id="iflzpo_mm_api_key" value="<?php echo $iflzpo_mm_api_key; ?>" type="text" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="iflzpo_mm_api_secret">MemberMouse API Secret</label></th>
				<td><input name="iflzpo_mm_api_secret" id="iflzpo_mm_api_secret" value="<?php echo $iflzpo_mm_api_secret; ?>" type="password" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="iflzpo_token_reader_count">Token Reader Count</label></th>
				<td><input name="iflzpo_token_reader_count" id="iflzpo_token_reader_count" value="<?php echo $iflzpo_token_reader_count; ?>" type="text" class="regular-text" /></td>
			</tr>
		</tbody>	
	</table>
	
	<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	
</form>

<?php include 'admin-footer.inc.php'; ?>
