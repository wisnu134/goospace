<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

function loginizer_pro_passkey(){
	global $lz_error;

	$passkey_settings = get_option('loginizer_passkey_settings', [
		'general_settings' => [],
	]);

	if(isset($_POST['lz_pro_save_passkey']) || isset($_POST['lz_pro_save_webauthn'])){

		if(!current_user_can('manage_options')){
			$lz_error['invalid_capability'] = __('Sorry, you do not have permission to acces this page', 'loginizer');
			lz_report_error($lz_error);
			return loginizer_pro_passkey_T();
		}

		if(!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'loginizer-passkey')){
			$lz_error['invalid_nonce'] = __('Empty or invalid nonce field.', 'loginizer');
			lz_report_error($lz_error);
			return loginizer_pro_passkey_T();
		}
		
		// save general settings
		if(isset($_POST['lz_pro_save_passkey'])){
			$passkey_settings['general_settings']['enable_passkey'] = !empty($_POST['loginizer_enable_passkey']);
			$passkey_settings['general_settings']['maximum_passkeys'] = !empty($_POST['loginizer_maximum_passkeys']) ? sanitize_text_field(wp_unslash($_POST['loginizer_maximum_passkeys'])) : '3';
			$passkey_settings['general_settings']['exclude_roles'] = !empty($_POST['loginizer_passkey_exclude_roles']) ? map_deep(wp_unslash($_POST['loginizer_passkey_exclude_roles']), 'sanitize_text_field') : [];
			$passkey_settings['general_settings']['auto_passkey_name'] = !empty($_POST['loginizer_generate_passkey_name']);
			$passkey_settings['general_settings']['wc_login'] = !empty($_POST['loginizer_wc_login']);
			$passkey_settings['general_settings']['um_login'] = !empty($_POST['loginizer_um_login']);
			$passkey_settings['general_settings']['registration_email'] = !empty($_POST['loginizer_passkey_registration_email']);
		}

		if(update_option('loginizer_passkey_settings', $passkey_settings)){
			$GLOBALS['lz_passkey_saved'] = true;
		}
	}

	loginizer_pro_passkey_T();
}

function loginizer_pro_passkey_T(){

	global $wp_roles;

	loginizer_page_header('Passkey Settings');

	$passkey_settings = get_option('loginizer_passkey_settings', [
		'general_settings' => [],
	]);
	
	// TODO: Remove sanitization
	$exclude_roles = !empty($passkey_settings['general_settings']['exclude_roles']) ? $passkey_settings['general_settings']['exclude_roles'] : [];

	// Any error occured ?
	if(!empty($lz_error)){
		lz_report_error($lz_error);
		echo '<br />';
	}

	$meta_key = [
		'meta_key' => 'loginizer_public_key_credentials',
		'fields' => 'ids',
		'count_total' => true
	];

	// Saved successfully ?
	if(!empty($GLOBALS['lz_passkey_saved'])){
		echo '<div id="message" class="updated"><p>'. (is_string($GLOBALS['lz_passkey_saved']) ? esc_html($GLOBALS['lz_passkey_saved']) : esc_html__('The settings were saved successfully', 'loginizer')). '</p></div><br />';
	}

	// Remind the admin to set up their own passkey, it goes away once they have one
	if(!empty($passkey_settings['general_settings']['enable_passkey']) && empty(get_user_meta(get_current_user_id(), 'loginizer_public_key_credentials', true))){
		echo '<div class="notice notice-info"><p>'.esc_html__('Passkey login is enabled, but you have not set up a passkey yet.', 'loginizer').' <a href="'.esc_url(admin_url('?page=loginizer_user')).'">'.esc_html__('Set up your passkey on the My Security page', 'loginizer').'</a></p></div><br />';
	}

	$all_roles = array_keys( $wp_roles->roles );
	$allowed_roles = array_diff( $all_roles, array('administrator'));

	echo '
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle ui-sortable-handle">
					<span>'. esc_html__('General Settings', 'loginizer').'</span>
				</h2>
			</div>
			<div class="inside">
				<form action="" method="post">';
				wp_nonce_field('loginizer-passkey');
				echo '<table class="form-table">
						<tr>
							<th scope="row" valign="top">
								<label for="loginizer_enable_passkey">'.esc_html__('Enable Passkey', 'loginizer').'</label>
							</th>
							<td>
								<input type="checkbox" name="loginizer_enable_passkey" id="loginizer_enable_passkey" '.(checked(!empty($passkey_settings['general_settings']['enable_passkey']), true, false)).'>
								<span class="description">'. esc_html__('Enable passkey generation', 'loginizer').'</span>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label>'.esc_html__('Number of Passkeys', 'loginizer').'</label>
							</td>
							<td>
								<input type="number" name="loginizer_maximum_passkeys" min="1" value="'.(!empty($passkey_settings['general_settings']['maximum_passkeys']) ? esc_attr($passkey_settings['general_settings']['maximum_passkeys']) : '3').'" style="width:10%;"><br />
								<span class="description">'.esc_html__('Set the maximum number of passkeys a user can generate', 'loginizer').'</span>
							</td>
						</tr>
						<tr>
							<th scope="row" valign="top">
								<label>'.esc_html__('Exclude User Roles', 'loginizer').'</label>
							</th>
							<td>';
						foreach($allowed_roles as $role){
							echo '<input type="checkbox" name="loginizer_passkey_exclude_roles[]" value="'.esc_attr($role).'" id="loginizer_passkey_role_'.esc_attr($role).'" '.(checked(in_array($role, $exclude_roles), true, false)).'>
							<label for="loginizer_passkey_role_'.esc_attr($role).'">'.esc_html(ucwords(str_replace('_', ' ', $role)), 'loginizer').'</label><br />';
						}
						echo '</td>
						</tr>
						<tr>
							<th>
								<label for="loginizer_generate_passkey_name">'.esc_html__('Automatic Passkey Name Generation', 'loginizer').'</label>
							</th>
							<td>
								<input type="checkbox" name="loginizer_generate_passkey_name" id="loginizer_generate_passkey_name" '.(checked(!empty($passkey_settings['general_settings']['auto_passkey_name']), true, false)).'>
								<span>'.esc_html__('If enabled, the 10 character passkey name with "Passkey_" prefix will be generated automatically while generating new passkey.', 'loginizer').'</span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="loginizer_passkey_registration_email">'.esc_html__('Passkey Registration Email', 'loginizer').'</label>
							</th>
							<td>
								<input type="checkbox" name="loginizer_passkey_registration_email" id="loginizer_passkey_registration_email" '.(checked(!empty($passkey_settings['general_settings']['registration_email']), true, false)).'>
								<span>'.esc_html__('If enabled, the user will get an email whenever a new passkey is added to their account, so they can spot a passkey they did not add.', 'loginizer').'</span>
							</td>
						</tr>
						<tr>
							<th>
								<label>'.esc_html__('Enable Passkey Login for', 'loginizer').'</label>
							</th>
							<td>
								<input type="checkbox" id="loginizer_cwp_login" checked disabled>
								<label>'.esc_html__('Wordpress login', 'loginizer').'</label></br>
								<input type="checkbox" name="loginizer_wc_login" value="1" id="loginizer_wc_login"  '.(checked(!empty($passkey_settings['general_settings']['wc_login']), true, false)).'>
								<label for="loginizer_wc_login">'.esc_html__('WooCommerce login', 'loginizer').'</label></br>
								<input type="checkbox" name="loginizer_um_login" value="1" id="loginizer_um_login"  '.(checked(!empty($passkey_settings['general_settings']['um_login']), true, false)).'>
								<label for="loginizer_um_login">'.esc_html__('Ultimate Member login', 'loginizer').'</label>
							</td>
						</tr>
					</table>
					<div class="lz_pro_firewall_save_btn"><input name="lz_pro_save_passkey" class="button button-primary action" value="'.esc_attr__('Save Settings', 'loginizer').'" type="submit" /></div>
				</form>
			</div>
		</div>';

	loginizer_page_footer();
}