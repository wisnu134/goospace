<?php

namespace LoginizerPro;

if(!defined('ABSPATH')){
	die('Hacking Attempt');
}

class Passkey{

	public static function init_hooks(){
		global $loginizer;
			
		$wc_login = $um_login = false;
		if(!empty($loginizer['passkey_settings'])){
			$wc_login = !empty($loginizer['passkey_settings']['general_settings']['wc_login']);
			$um_login = !empty($loginizer['passkey_settings']['general_settings']['um_login']);
		}
		
		// Core WP login form script
		add_action('login_enqueue_scripts', [__CLASS__, 'enqueue_script']);
		add_action('login_form', function(){
			// parameters -> (is core Wp, is UM)
			self::login_button(true, false);// For core WP
		}, 10003);

		if($um_login && !empty($loginizer['ultimate-member-active']) && class_exists('UM') && !is_user_logged_in()){
			add_action('um_after_login_fields', function(){
				// parameters -> (is core Wp, is UM)
				self::login_button(false, true);// For UM plugin
			}, 10003);
			add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_script']);
		}

		if($wc_login && class_exists('WooCommerce') && !is_user_logged_in()){
			add_action('woocommerce_login_form_end', function(){
				// parameters -> (is core Wp, is UM)
				self::login_button(false, false);// For WC plugin
			}, 10003);
			add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_script']);
		}
	}

	// Add passkey script file for the registration and login
	public static function passkey_registration($cur_user){
		global $loginizer;

		$wc_login = !empty($loginizer['passkey_settings']['general_settings']['wc_login']);
		$um_login = !empty($loginizer['passkey_settings']['general_settings']['um_login']);
		if(!is_admin()){
			if(function_exists('is_account_page') && is_account_page() && !$wc_login){
				return;
			}else if(function_exists('um_is_core_page') && um_is_core_page('account') && !$um_login){
				return;
			}
		}

		$user_id = $cur_user->ID;
		// Delete the passkey
		if(isset($_POST['lz_delete_passkey'])){

			/* Make sure post was from this page */
			if(count($_POST) > 0){
				check_admin_referer('loginizer-user');
			}

			$passkey_id = sanitize_text_field(wp_unslash($_POST['lz_delete_passkey']));
			
			$passkeys = get_user_meta($user_id, 'loginizer_public_key_credentials', true);
			
			if(!empty($passkeys[$passkey_id])){
				unset($passkeys[$passkey_id]);
				update_user_meta($user_id, 'loginizer_public_key_credentials', $passkeys);
			}
		}

		$user_passkeys = get_user_meta($user_id, 'loginizer_public_key_credentials', true);
		// Enqueue CSS and the JS files
		self::enqueue_script();

		// Passkey Settings
		echo '<div class="loginizer_passkey_settings">'.(!is_admin() ? '<br>' : '').'
			<h2>'.esc_html__('Loginizer Passkey Settings', 'loginizer').'
		</h2>';

		if(is_admin()){
			echo '<div id="message" class="error" style="display:none;padding:5px 2.5px;"></div>';
		}else if(function_exists('is_account_page') && is_account_page()){
			echo '<div id="message" class="woocommerce-error" style="display:none;"></div>';
		}else if(function_exists('um_is_core_page') && um_is_core_page('account')){
			echo '<div id="message" class="um-field-error" style="display:none;"></div>';
		}

		echo '<div class="loginizer_passkey_btn_con">
			<button type="button" class="button wp-element-button" id="loginizer_register_passkey">'.esc_html__('Register New Passkey', 'loginizer').'</button>
			'.(is_admin() ? '<div class="spinner loginizer_passkey_loader_a" style="display:none;"></div>' : '').'
		</div>
		<div class="loginizer-passkey-name-container" style="display:none;"><div class="loginizer-passkey-name">
			<input type="text" name="loginizer_passkey_name" id="loginizer_passkey_name" placeholder="'.esc_html__('Enter passkey name...', 'loginizer').'">
			'.(is_admin() ? '<div class="spinner loginizer_passkey_loader_b" style="display:none;"></div>' : '').'
			<button type="button" class="button wp-element-button" id="loginizer_add_passkey_name">'.esc_html__('Add', 'loginizer').'</button>
			<button type="button" class="button wp-element-button" id="loginizer_cancel_passkey_name">'.esc_html__('Cancel', 'loginizer').'</button>
		</div></div></br>';

		echo '<form action="" method="post">'.wp_nonce_field('loginizer-user', '_wpnonce', true, false).'
		<table '.(is_admin() ? 'class="wp-list-table widefat users striped" style="width:70%;"' : 'class="loginizer_passkey_table"').'"><thead>
			<tr>
				<th>'.esc_html__('Passkey Id', 'loginizer').'</th>
				<th>'.esc_html__('Authenticator', 'loginizer').'</th>
				<th>'.esc_html__('Date Created', 'loginizer').'</th>
				<th>'.esc_html__('Last Used', 'loginizer').'</th>
				<th>'.esc_html__('Delete', 'loginizer').'</th>
			</tr></thead><tbody>';
		if(!empty($user_passkeys)){
			foreach($user_passkeys as $passkey){
				$icon_val = !empty($passkey['authenticator_icon']) ? $passkey['authenticator_icon'] : 'default';
				$auth_name = !empty($passkey['authenticator']) ? $passkey['authenticator'] : __('Security Key / Passkey', 'loginizer');

				echo '<tr>
					<td>'.esc_html($passkey['passkey_name']).'</td>
					<td><span class="loginizer_passkey_authenticator">
						<img src="'.esc_url(self::get_icon_url($icon_val)).'" width="20" height="20" alt="" style="vertical-align:middle;margin-right:6px;">
						<span style="font-weight:600;font-size:14px;">'.esc_html($auth_name).'</span></span>
					</td>
					<td>'.esc_html($passkey['date_created']).'</td>
					<td>'.esc_html($passkey['last_used']).'</td>
					<td><button type="submit" name="lz_delete_passkey" class="button wp-element-button loginizer-delete-passkey" value="'.esc_attr($passkey['credentialId']).'">'.esc_html__('Delete', 'loginizer').'</button></td>
				</tr>';
			}
		}else{
			echo '<tr>
				<td colspan="6">'.esc_html__('No passkeys! Passkeys will be listed here.', 'loginizer').'</td>
			</tr>';
		}
		echo '</tbody></table></form></div><br>';

	}

	public static function enqueue_script(){
		
		// Add CSS file only if the page is loginizer-security
		if(is_user_logged_in()){
			wp_enqueue_style('loginizer-passkey-css', LOGINIZER_PRO_DIR_URL . 'assets/css/passkey.css', [], LOGINIZER_PRO_VERSION);
		}

		wp_enqueue_script('loginizer-pro-passkey', LOGINIZER_PRO_DIR_URL . 'assets/js/passkey.js', ['jquery'], LOGINIZER_PRO_VERSION, true);
		wp_localize_script('loginizer-pro-passkey', 'loginizer_pro_passkey', [
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('loginizer_pro_passkey_nonce'),
			'strings' =>[
				'auth_failed' => __('Passkey authentication failed. try again or login via password.', 'loginizer'),
				'passkey_exist' => __('You have already registered this device. You do not have to register again.', 'loginizer'),
				'passkey_limit' => __('Maximum passkey limit reached for this account', 'loginizer'),
				'delete_passkey' => __('Are you sure you want to delete this passkey ?', 'loginizer'),
				'empty_name' => __('Passkey name is invalid or empty. Should contains only (Alphabets, Numbers, Underscores and Hyphens).', 'loginizer'),
				'already_login' => __('You are already logged in.', 'loginizer'),
				'not_supported' => __('Your browser does not supports the passkey login.', 'loginizer'),
				'redirecting' => __('Redirecting...', 'loginizer')
			]
		]);
	}

	public static function login_button($core_wp = true, $um = true){

		// Add CSS for the button and the error message
		echo '<style>
			.loginizer_passkey_login_block{
				'.($core_wp ? 'margin-top:25px;margin-bottom:10px;' : '').'
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				width: 100%;
			}
			.loginizer_passkey_login_error{
				display: none;
				margin: 0 0 10px 0;
				padding: 8px 12px;
				color: #d63638;
				background-color: #fcf0f1;
				border-left: 4px solid #d63638;
				font-size: 13px;
				line-height: 1.4;
				text-align: left;
				width: 100%;
				box-sizing: border-box;
				border-radius: 2px;
			}
			#loginizer_passkey_login{
				margin-top: 5px;
				cursor: pointer;
				transition: all 0.2s ease-in-out;
			}
			#loginizer_passkey_login.is-redirecting{
				cursor: wait !important;
				opacity: 0.95;
			}
		</style>';
		
		echo '<div class="loginizer_passkey_login_block '.($um ? 'um-col-alt' : '').'">
			<span class="loginizer_passkey_login_error"></span>
			<button id="loginizer_passkey_login" type="button" class="wp-element-button um-button button" 
			'.($um ? 'style="color:#fff;background-color:#3ba1da;"' : 'style="width:fit-content;"').'>
			'.esc_html__('Login With Passkey', 'loginizer').'</button>
		</div>';
	}

	/**
	 * Returns authenticator icon URL
	 * @param string $icon
	 * @return string
	 */
	public static function get_icon_url($icon = 'default'){

		$icon_name = sanitize_file_name($icon);
		$file_path = LOGINIZER_PRO_DIR . 'assets/images/img/' . $icon_name . '.svg';

		if(!empty($icon_name) && file_exists($file_path)){
			return LOGINIZER_PRO_DIR_URL . 'assets/images/img/' . $icon_name . '.svg';
		}

		return LOGINIZER_PRO_DIR_URL . 'assets/images/img/default.svg';
	}

	/**
	 * Returns inline SVG markup for the authenticator icon
	 * @param string $icon
	 * @return string
	 */
	public static function get_icon_svg($icon = 'default'){

		$icon_name = sanitize_file_name($icon);
		$file_path = LOGINIZER_PRO_DIR . 'assets/images/img/' . $icon_name . '.svg';

		if(!empty($icon_name) && file_exists($file_path)){
			return file_get_contents($file_path);
		}

		$default_path = LOGINIZER_PRO_DIR . 'assets/images/img/default.svg';
		if(file_exists($default_path)){
			return file_get_contents($default_path);
		}

		return '';
	}
}
