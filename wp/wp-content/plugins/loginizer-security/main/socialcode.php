<?php

namespace LoginizerPro;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class SocialCode extends \Loginizer_Social_Base{

	// Hooked on loginizer_social_user, which free only fires once its social-base.php is loaded
	public static function microsoft_user($user, $provider, $data){

		global $loginizer;

		if($provider != 'MicrosoftGraph'){
			return $user;
		}

		if(empty($data['identifier'])){
			return new \WP_Error('lz_microsoft_id', __('Microsoft did not return an account id.', 'loginizer'));
		}

		$linked = self::linked_user($data['identifier']);
		if(!empty($linked)){
			return $linked;
		}

		$user = get_user_by('email', sanitize_email($data['email']));

		if(!empty($user) && !empty(get_user_meta($user->ID, 'loginizer_microsoft_id', true))){
			return new \WP_Error('lz_microsoft_id', __('This Microsoft account is not the one linked to this user.', 'loginizer'));
		}

		// Nothing to link and registration is off, the caller shows its own error
		if(empty($user) && empty($loginizer['social_settings']['general']['register_new'])){
			return $user;
		}

		$sent = self::send_code($user, $data);
		if(is_wp_error($sent)){
			return $sent;
		}

		return new \WP_Error('loginizer_social_redirect', '', add_query_arg('action', 'loginizer_social_code', wp_login_url()));
	}

	protected static function linked_user($identifier){

		// blog_id 0 searches the whole network, same as get_user_by('email')
		$users = get_users(['meta_key' => 'loginizer_microsoft_id', 'meta_value' => (string) $identifier, 'number' => 1, 'blog_id' => 0]);

		return !empty($users[0]) ? $users[0] : false;
	}

	// Emails an 8 digit code and keeps what is needed to finish the login for 15 minutes, tied to this browser by a cookie.
	// It goes to the email of the WordPress user, or to the one Microsoft gave when registering.
	protected static function send_code($user, $data){

		$email = !empty($user) ? $user->user_email : sanitize_email($data['email']);
		$email_key = md5(strtolower($email));

		// Counted per email and not per login, so starting a new login does not give more guesses
		if(get_transient('lz_code_fails_'.$email_key) >= 5){
			return new \WP_Error('lz_social_code', __('Too many wrong codes were entered, please try again in an hour.', 'loginizer'));
		}

		// One email every 2 minutes, so the login can not be used to flood an inbox
		if(get_transient('lz_code_sent_'.$email_key)){
			return new \WP_Error('lz_social_code', __('A code was emailed a moment ago, please wait 2 minutes before trying again.', 'loginizer'));
		}

		// 8 digits, as parallel guesses can get past the wrong code limit before it is saved
		$code = sprintf('%08d', random_int(0, 99999999));
		unset($data['access_token']);

		$pending = [
			'uid' => !empty($user) ? $user->ID : 0,
			'email' => $email,
			'data' => $data,
			'hash' => wp_hash($code),
			'ref' => self::$ref,
		];

		$site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
		$subject = sprintf(__('[%s] Your sign in code', 'loginizer'), $site_name);
		$message = sprintf(__("Someone is signing in to %1\$s with a Microsoft account using this email address.\n\nYour code is: %2\$s\n\nIt expires in 15 minutes. If this was not you, ignore this email and do not share the code with anyone.", 'loginizer'), $site_name, $code);

		if(!wp_mail($email, $subject, $message)){
			return new \WP_Error('lz_social_code', __('There was a problem sending your email. Please try again or contact an admin.', 'loginizer'));
		}

		$key = 'lz_code_' . bin2hex(random_bytes(12));
		set_transient($key, $pending, 15 * MINUTE_IN_SECONDS);
		set_transient('lz_code_sent_'.$email_key, 1, 2 * MINUTE_IN_SECONDS);
		setcookie('lz_social_code', $key, time() + 15 * MINUTE_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

		return true;
	}

	// The page on wp-login.php where the emailed code is entered
	public static function page(){

		$key = '';
		if(!empty($_COOKIE['lz_social_code']) && is_string($_COOKIE['lz_social_code']) && preg_match('/^lz_code_[0-9a-f]{24}$/', $_COOKIE['lz_social_code'])){
			$key = $_COOKIE['lz_social_code'];
		}

		$pending = !empty($key) ? get_transient($key) : false;

		if(empty($pending)){
			self::end($key, __('This sign in has expired, please sign in with Microsoft again.', 'loginizer'));
		}

		// Wrong codes count as failed logins, so a locked out IP can not keep guessing
		if(!loginizer_can_login()){
			self::end($key, __('You have exceeded the maximum login retries, please try again later.', 'loginizer'));
		}

		$errors = new \WP_Error();

		if(isset($_POST['lz_social_code'])){

			$fails_key = 'lz_code_fails_'.md5(strtolower($pending['email']));
			$fails = (int) get_transient($fails_key);
			$code = is_string($_POST['lz_social_code']) ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['lz_social_code'])) : '';

			if(empty($_POST['lz_social_code_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['lz_social_code_nonce'])), 'loginizer_social_code')){
				$errors->add('lz_social_code', __('Security check failed, please try again.', 'loginizer'));

			}elseif($fails >= 5){
				self::end($key, __('Too many wrong codes were entered, please try again in an hour.', 'loginizer'));

			}elseif(!hash_equals($pending['hash'], wp_hash($code))){
				set_transient($fails_key, $fails + 1, HOUR_IN_SECONDS);
				do_action('wp_login_failed', $pending['email']);
				$errors->add('lz_social_code', __('The code is incorrect.', 'loginizer'));

			}else{
				delete_transient($fails_key);
				self::forget($key); // finish() redirects on success, so the code is used up first
				self::end($key, self::finish($pending));
			}
		}

		login_header(__('Enter code', 'loginizer'), '<p class="message">'.esc_html__('We have emailed you an 8 digit code. Enter it below to finish signing in with Microsoft.', 'loginizer').'</p>', $errors);
		?>
		<form name="lz_social_code_form" id="loginform" method="post" action="<?php echo esc_url(add_query_arg('action', 'loginizer_social_code', wp_login_url())); ?>">
			<p>
				<label for="lz_social_code"><?php esc_html_e('Code', 'loginizer'); ?></label>
				<input type="text" name="lz_social_code" id="lz_social_code" class="input" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required autofocus>
			</p>
			<?php wp_nonce_field('loginizer_social_code', 'lz_social_code_nonce'); ?>
			<p class="submit">
				<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e('Continue', 'loginizer'); ?>">
			</p>
		</form>
		<?php
		login_footer('lz_social_code');
		exit;
	}

	// Links the Microsoft account, registering the user first if needed, then logs in and redirects. Returns the error message otherwise.
	protected static function finish($pending){

		global $loginizer;

		$identifier = (string) $pending['data']['identifier'];

		// Another login could have linked the id while the code was pending
		$linked = self::linked_user($identifier);
		if(!empty($linked) && $linked->ID != $pending['uid']){
			return __('This Microsoft account is linked to another user.', 'loginizer');
		}

		if(!empty($pending['uid'])){
			$user = get_user_by('id', $pending['uid']);

			if(empty($user)){
				return __('User not found, please sign in with Microsoft again.', 'loginizer');
			}

			$user_linked = get_user_meta($user->ID, 'loginizer_microsoft_id', true);
			if(!empty($user_linked) && $user_linked !== $identifier){
				return __('This Microsoft account is not the one linked to this user.', 'loginizer');
			}

		}elseif(email_exists($pending['email'])){
			return __('An account with this email was created meanwhile, please sign in with Microsoft again.', 'loginizer');

		// Registration could have been turned off while the code was pending
		}elseif(empty($loginizer['social_settings']['general']['register_new'])){
			return __('You can not register through Social Login', 'loginizer');

		}else{
			$user = self::create_account($pending['data']);

			if(empty($user) || is_wp_error($user)){
				return is_wp_error($user) ? $user->get_error_message() : __('Unable to register your account, try again later!', 'loginizer');
			}
		}

		update_user_meta($user->ID, 'loginizer_microsoft_id', $identifier);

		$authenticated = loginizer_wp_authenticate($user, $user->user_login, $user->user_pass);
		if(is_wp_error($authenticated)){
			return $authenticated->get_error_message();
		}

		if(!self::login_user($user)){
			return !empty(self::$error) ? current(self::$error) : __('Unable to log you in, please try again.', 'loginizer');
		}

		wp_safe_redirect(!empty($pending['ref']) ? self::handle_redirect($pending['ref']) : admin_url());
		exit;
	}

	// Removes the pending login, so its code can not be used again
	protected static function forget($key){

		if(!empty($key)){
			delete_transient($key);
		}

		setcookie('lz_social_code', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}

	// Ends the pending login and shows why
	protected static function end($key, $message){

		self::forget($key);

		$errors = new \WP_Error('lz_social_code', $message);
		login_header(__('Enter code', 'loginizer'), '', $errors);
		echo '<p id="nav"><a href="'.esc_url(wp_login_url()).'">'.esc_html__('Back to login', 'loginizer').'</a></p>';
		login_footer();
		exit;
	}
}
