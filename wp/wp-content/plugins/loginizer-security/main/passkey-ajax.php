<?php

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT');
}

function loginizer_passkey_ajax_init(){

	// Passkey Registration
	add_action('wp_ajax_loginizer_pro_passkey_register', 'loginizer_pro_passkey_register');
	add_action('wp_ajax_loginizer_pro_passkey_verify_register', 'loginizer_pro_passkey_verify_register');

	// Paskey Login
	add_action('wp_ajax_loginizer_pro_passkey_authentication', 'loginizer_pro_passkey_authentication');
	add_action('wp_ajax_nopriv_loginizer_pro_passkey_authentication', 'loginizer_pro_passkey_authentication');
	add_action('wp_ajax_loginizer_pro_passkey_verify_authentication', 'loginizer_pro_passkey_verify_authentication');
	add_action('wp_ajax_nopriv_loginizer_pro_passkey_verify_authentication', 'loginizer_pro_passkey_verify_authentication');
}

// Challenge creation for passkey registration
function loginizer_pro_passkey_register(){
	check_ajax_referer('loginizer_pro_passkey_nonce', 'security');

	// User data
	$user = wp_get_current_user();

	if(!$user instanceof WP_User){
		wp_send_json_error(__('Some error occured, try after some time', 'loginizer'));
	}
	
	// rpId (domain)
	$rp_id = wp_parse_url(site_url());
	$rp_id = !empty($rp_id) ? $rp_id['host'] : '';

	// This is used for generating the multiple passkeys with the same Authenticator and device
	$user_public_keys = get_user_meta($user->ID, 'loginizer_public_key_credentials', true);
	$passkey_settings = loginizer_pro_get_passkey_settings();

	$exclude_credentials = [];
	if(!empty($user_public_keys) && is_array($user_public_keys)){

		if(count($user_public_keys) >= $passkey_settings['maximum_passkeys']){
			wp_send_json_success();
		}

		foreach($user_public_keys as $user_public_key){
			$exclude_credentials[] = base64_decode($user_public_key['credentialId']);
		}
	}

	try{

		// Initialize the library
		$webauthn = new \LoginizerPro\WebAuthn(
			get_bloginfo('name'),
			$rp_id
		);

		// Create the arguments for registering credentials to use at client side
		$get_args = $webauthn->getCreateArgs(
			(int) $user->ID,					// userId(userHandle)
			$user->user_login,							// userName
			$user->display_name,						// userDisplayName
			$passkey_settings['registration_timeout'],	// Timeout
			true,										// requireResidentalKey
			'required',									// requireUserVerification
			null,										// crossPltformAttachment
			!empty($passkey_settings['exclude_credentials']) ? $exclude_credentials : null	// excludeCredentials
		);
		// Set resident key to true for the small devices to access biometrics
		$get_args->publicKey->authenticatorSelection->residentKey = 'required';

		// Get the challenge and encode it
		$challenge = base64_encode($webauthn->getChallenge()->getBinaryString());

		// Store the transient to verify for passkey registration
		set_transient('loginizer_pro_passkey_challenge_' . $user->ID, $challenge, 5*MINUTE_IN_SECONDS);

		// User must enter passkey name if admin has enbaled it
		// For which we are going to add it in the publicKey
		$get_args->publicKey->passkeyName = $passkey_settings['auto_passkey_name'];

		// send the response to the browser
		wp_send_json_success($get_args);
	}catch(\Throwable $th){
		wp_send_json_error($th->getMessage());
	}
}

// Verify passkey creation and save public Key credentials in the user_meta
function loginizer_pro_passkey_verify_register(){
	check_ajax_referer('loginizer_pro_passkey_nonce', 'security');

	// Get the stored challenge
	$user_id = get_current_user_id();
	$challenge = get_transient('loginizer_pro_passkey_challenge_' . $user_id);

	if(!isset($_POST['credentials']) || empty($_POST['credentials']) || empty($challenge)){
		wp_send_json_error(__('Invalid Credentials', 'loginizer'));
	}
	$credentials = json_decode(wp_unslash($_POST['credentials']), true);

	$passkey_name = !empty($_POST['passkey_name']) ? sanitize_text_field(wp_unslash($_POST['passkey_name'])) : 'Passkey_' . loginizer_pro_generate_random_passkey_name();
	if(empty(preg_match('/^[a-zA-Z0-9_\-\s]+$/', $passkey_name))){
		wp_send_json_error(__('Passkey name is invalid or empty. Should contains only (Alphabets, Numbers, Underscores and Hyphens).', 'loginizer'));
	}

	$rp_id = wp_parse_url(site_url());
	$rp_id = !empty($rp_id) ? $rp_id['host'] : '';

	try{

		// Intialize the library with rpId and domain name
		$webauthn = new \LoginizerPro\WebAuthn(
			get_bloginfo('name'),
			$rp_id
		);

		// Verify the challenge and create the public key
		$process_create = $webauthn->processCreate(
			loginizer_pro_base64url_to_binary($credentials['credentials']['response']['clientDataJSON']),
			loginizer_pro_base64url_to_binary($credentials['credentials']['response']['attestationObject']),
			base64_decode($challenge),
			true	// requireUserVerification
		);

		// Get all the saved passkey if exists
		$user_public_keys = get_user_meta($user_id, 'loginizer_public_key_credentials', true);
		$user_public_keys = (!empty($user_public_keys) && is_array($user_public_keys)) ? $user_public_keys : [];

		$auth_info = loginizer_pro_get_authenticator_name($process_create->AAGUID);

		// User meta doesn't allow binary data, so we store base64-encoded values
		$user_public_keys[base64_encode($process_create->credentialId)] = [
			'AAGUID' => base64_encode($process_create->AAGUID),
			'credentialId' => base64_encode($process_create->credentialId),
			'credentialPublicKey' => base64_encode($process_create->credentialPublicKey),
			'signatureCounter' => $process_create->signatureCounter,
			'user_id' => $user_id,	// we will use this to login the user
			'passkey_name' => $passkey_name,
			'authenticator_icon' => $auth_info['icon'],
			'authenticator' => $auth_info['name'],
			'date_created' => date('Y-m-d H:i:s'),
			'last_used' => __('Not used yet', 'loginizer')
		];

		// Store the users public key credentials into the user meta
		update_user_meta($user_id, 'loginizer_public_key_credentials', $user_public_keys);

		// A stolen session can add a passkey to keep access, this lets the real owner notice it
		$passkey_settings = loginizer_pro_get_passkey_settings();
		if(!empty($passkey_settings['registration_email'])){
			loginizer_pro_passkey_registration_email(wp_get_current_user(), $passkey_name, $auth_info['name']);
		}

		// Delete challenge, (by default, it will be deleted after 5 minuts)
		delete_transient('loginizer_pro_passkey_challenge_' . $user_id);

		// Finally send response to the browser
		wp_send_json_success(__('Passkey Registered Successfully.', 'loginizer'));

	}catch(\Throwable $th){
		// Delete challenge, (by default, it will be deleted after 5 minuts)
		delete_transient('loginizer_pro_passkey_challenge_' . $user_id);
		wp_send_json_error($th->getMessage());
	}
}

//  Challenge creation for the login
function loginizer_pro_passkey_authentication(){
	global $loginizer;

	check_ajax_referer('loginizer_pro_passkey_nonce', 'security');

	if(is_user_logged_in()){
		wp_send_json_success();
	}

	// Blacklisted, locked out or non trusted IPs can not even get a challenge
	// Same as password login, these checks only apply when Brute Force is enabled
	if(empty($loginizer['disable_brute'])){
		$blocked = loginizer_wp_authenticate(null, '', '');
		if(is_wp_error($blocked)){
			wp_send_json_error(wp_strip_all_tags($blocked->get_error_message()));
		}
	}

	$rp_id = wp_parse_url(site_url());
	$rp_id = !empty($rp_id) ? $rp_id['host'] : '';

	$passkey_settings = loginizer_pro_get_passkey_settings();

	try{

		// Initialize the lib
		$webauthn = new \LoginizerPro\WebAuthn(
			get_bloginfo('name'),
			$rp_id
		);

		// Initialize the login process
		$get_args = $webauthn->getGetArgs(
			[],	// Passing null for the username less login
			$passkey_settings['login_timeout'],	// Timeout
			false,	// allowUsb
			false,	// allowNfc
			false,	// allowBle
			true,	// allowHybrid
			true,	// allowinternal 
			'required'	// requireUserVerification
		);

		// Get the challenge 
		$challenge = base64_encode($webauthn->getChallenge()->getBinaryString());

		// Generate a UUID to identify user specific challenge
		// We will send this identifier to the browser and get back with publicKeyCredentials
		// This will help to identify the user specific challenge and publicKeyCredentials 
		$request_id = str_replace('-', '', wp_generate_uuid4());

		// Store the transient to verify for passkey registration
		set_transient('loginizer_pro_passkey_login_challenge_' . $request_id, $challenge, 5*MINUTE_IN_SECONDS);

		// Send the ceredentials and identifier to the browser
		wp_send_json_success(['public_key' => $get_args, 'request_id' => $request_id]);

	}catch(\Throwable $th){
		wp_send_json_error($th->getMessage());
	}
}

// Verify login challenge and authentication
function loginizer_pro_passkey_verify_authentication(){
	global $loginizer;

	check_ajax_referer('loginizer_pro_passkey_nonce', 'security');

	if(!isset($_POST['credentials']) || empty($_POST['credentials'])){
		wp_send_json_error(__('Invalid Credentials.', 'loginizer'));
	}

	if(!isset($_POST['request_id']) || empty($_POST['request_id'])){
		wp_send_json_error(__('Invalid Credentials', 'loginizer'));
	}

	// Get the challenge and the publicKeyCredentials from the browser
	// The identifier we created earlier, we'll use here
	$request_id = sanitize_text_field(wp_unslash($_POST['request_id']));
	$challenge = get_transient('loginizer_pro_passkey_login_challenge_' . $request_id);

	// Challenge is single use, so a captured assertion can not be replayed
	delete_transient('loginizer_pro_passkey_login_challenge_' . $request_id);
	$credentials = json_decode(wp_unslash($_POST['credentials']), true);

	if(json_last_error() !== JSON_ERROR_NONE){ 
		wp_send_json_error(__('Some error occurred. Try again or login via password.', 'loginizer'));
	}

	if(empty($challenge)){
		wp_send_json_error(__('Authentication challenge expired or invalid. Please try again.', 'loginizer'));
	}
	
	// Get the user_id from the userHandle we provide while generation of the passkey
	$user_handle = (!empty($credentials['credentials']['response']['userHandle']) && is_string($credentials['credentials']['response']['userHandle']))
		? loginizer_pro_base64url_to_binary($credentials['credentials']['response']['userHandle'])
		: '';
	$user_id = (int) $user_handle;

	// we are using Base64Url -> binary function and encode it to get Base64-string back
	$input_credential_id = !empty($credentials['credentials']['id'])
		? base64_encode(loginizer_pro_base64url_to_binary($credentials['credentials']['id']))
		: '';

	// Passkeys are registered as discoverable credentials, so the authenticator always returns the userHandle
	$user = get_user_by('ID', $user_id);
	$username = !empty($user) ? $user->user_login : '';

	// Passkeys must honour the IP blacklist, lockouts and Trusted IPs like every other login
	if(empty($loginizer['disable_brute'])){
		$authenticated = loginizer_wp_authenticate($user, $username, '');
		if(is_wp_error($authenticated)){
			wp_send_json_error(wp_strip_all_tags($authenticated->get_error_message()));
		}
	}

	$user_public_keys = !empty($user) ? get_user_meta($user_id, 'loginizer_public_key_credentials', true) : [];

	// One error for every case, so the response does not reveal which users exist, their role or if they have passkeys
	if(empty($user) || !loginizer_is_passkey_login_applicable($user) || (is_multisite() && is_user_spammy($user)) || !is_array($user_public_keys) || empty($input_credential_id) || !isset($user_public_keys[$input_credential_id])){
		$error = new WP_Error('invalid_passkey', __('Invalid credentials or wrong passkey!', 'loginizer'));

		// Counts towards brute force lockout like a wrong password
		do_action('wp_login_failed', $username, $error);
		wp_send_json_error($error->get_error_message());
	}

	// get the public key credentials of the selected passkey
	$matched_public_key_credentials = $user_public_keys[$input_credential_id];

	$rp_id = wp_parse_url(site_url());
	$rp_id = !empty($rp_id) ? $rp_id['host'] : '';

	try{

		$webauthn = new \LoginizerPro\WebAuthn(
			get_bloginfo('name'),
			$rp_id
		);

		// Verify the login
		$process_get = $webauthn->processGet(
			loginizer_pro_base64url_to_binary($credentials['credentials']['response']['clientDataJSON']),
			loginizer_pro_base64url_to_binary($credentials['credentials']['response']['authenticatorData']),
			loginizer_pro_base64url_to_binary($credentials['credentials']['response']['signature']),
			base64_decode($matched_public_key_credentials['credentialPublicKey']),	// Stored Public key
			base64_decode($challenge),	// Stored challenge
			isset($matched_public_key_credentials['signatureCounter']) && $matched_public_key_credentials['signatureCounter'] !== null ? (int) $matched_public_key_credentials['signatureCounter'] : null,
			true	// requireUserVerification
		);

		// Update the signatureCounter value
		$new_signature_counter = $webauthn->getSignatureCounter();
		if($new_signature_counter !== null && $new_signature_counter > (int) $matched_public_key_credentials['signatureCounter']){
			$user_public_keys[$input_credential_id]['signatureCounter'] = (int) $new_signature_counter;	
		}
		// Update the last used time
		$user_public_keys[$input_credential_id]['last_used'] = date('Y-m-d H:i:s');

		update_user_meta($user_id, 'loginizer_public_key_credentials', $user_public_keys);

		// Concurrent session limit must be checked before the new session exists, same as social login
		$limit_session = apply_filters('loginizer_pro_limit_sessions', $user);
		if(is_wp_error($limit_session)){
			wp_send_json_error($limit_session->get_error_message());
		}

		// Login the user and redirect to home url or redirect_to
		if(loginizer_pro_login_after_passkey_verification((int) $matched_public_key_credentials['user_id'])){
			$redirect_to = !empty($_REQUEST['redirect_to']) ? wp_sanitize_redirect($_REQUEST['redirect_to']) : '';
			if(empty($redirect_to)){
				$redirect_to = admin_url();
			}
			$redirect_to = apply_filters('login_redirect', $redirect_to, !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '', $user);

			// Same as wp_safe_redirect() in wp-login.php, no redirecting to external hosts
			wp_send_json_success(wp_validate_redirect($redirect_to, admin_url()));
		}else{
			wp_send_json_error(__('User not verified during login', 'loginizer'));
		}

	}catch(\Throwable $th){
		// Bad signature, challenge or origin, counts towards brute force lockout like a wrong password
		do_action('wp_login_failed', $username, new WP_Error('invalid_passkey', $th->getMessage()));
		wp_send_json_error($th->getMessage());
	}
}

// Convert Base64Url-encoded string into binary
function loginizer_pro_base64url_to_binary($base64url){

	if(empty($base64url) || !is_string($base64url)){
		return '';
	}

	$base64url = strtr($base64url ,'-_', '+/');
	$padding = strlen($base64url) % 4;

	if($padding > 0){
		$base64url .= str_repeat('=', 4 - $padding);
	}

	return base64_decode($base64url);
}

// Login the user after successfull authentication
function loginizer_pro_login_after_passkey_verification($user_id){

	if(empty($user_id)){
		return false;
	}

	$user = get_user_by('ID', $user_id);
	if(empty($user) || !($user instanceof WP_User)){
		return false;
	}

	// login the user
	wp_clear_auth_cookie();
	wp_set_current_user($user->ID);
	wp_set_auth_cookie($user->ID, true);
	do_action('wp_login', $user->user_login, $user);

	return true;
}

// Tells the user a new passkey was added to their account
function loginizer_pro_passkey_registration_email($user, $passkey_name, $authenticator){
	global $loginizer;

	if(empty($user->user_email)){
		return;
	}

	$sitename = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	/* translators: %s: Site name */
	$subject = sprintf(__('[%s] New passkey added to your account', 'loginizer'), $sitename);

	/* translators: 1: User login, 2: Site name, 3: Passkey name, 4: Authenticator name, 5: Date, 6: IP address */
	$message = sprintf(__("Hi %1\$s,\n\nA new passkey was added to your account on %2\$s.\n\nPasskey name: %3\$s\nAuthenticator: %4\$s\nDate: %5\$s\nIP address: %6\$s\n\nIf you did not add this passkey, delete it from your account security settings and change your password right away.", 'loginizer'),
		$user->user_login,
		$sitename,
		$passkey_name,
		$authenticator,
		date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
		$loginizer['current_ip']
	);

	wp_mail($user->user_email, $subject, $message);
}

function loginizer_pro_generate_random_passkey_name(){

	$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$random_string = '';

	for($i = 0; $i < 10; $i++){
		// Use random_int for better randomness
		$random_string .= $chars[random_int(0, strlen($chars) - 1)];
	}

	return $random_string;
}

function loginizer_pro_get_passkey_settings(){
	global $loginizer;

	$settings = !empty($loginizer['passkey_settings']) ? $loginizer['passkey_settings'] : get_option('loginizer_passkey_settings', []);

	// General settings
	$enable_passkey = !empty($settings['general_settings']['enable_passkey']);
	$auto_passkey_name = empty($settings['general_settings']['auto_passkey_name']);
	$registration_email = !empty($settings['general_settings']['registration_email']);
	$maximum_passkeys = !empty($settings['general_settings']['maximum_passkeys']) 
	? $settings['general_settings']['maximum_passkeys'] : 3;
	
	// Webauthn Settings
	$registration_timeout = 30;
	$login_timeout = 30;
	$exclude_credentials = true;

	return [
		'enable_passkey' => $enable_passkey,
		'auto_passkey_name' => $auto_passkey_name,
		'registration_email' => $registration_email,
		'maximum_passkeys' => (int) $maximum_passkeys,
		'registration_timeout' => (int) $registration_timeout,
		'login_timeout' => (int) $login_timeout,
		'exclude_credentials' => $exclude_credentials
	];
}

function loginizer_pro_get_authenticator_name($aaguid){

	// Convert binary 16-byte AAGUID to UUID string
	if(is_string($aaguid) && strlen($aaguid) === 16){
		$hex = bin2hex($aaguid);
		$uuid = sprintf(
			'%s-%s-%s-%s-%s',
			substr($hex, 0, 8),
			substr($hex, 8, 4),
			substr($hex, 12, 4),
			substr($hex, 16, 4),
			substr($hex, 20)
		);
	}else if(is_string($aaguid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $aaguid)){
		$uuid = strtolower($aaguid);
	}else{
		$uuid = '';
	}

	$known = [
		// Apple / iCloud Keychain
		'dd4ec289-e01d-41c9-bb89-70fa845d4bf2' => ['name' => 'Apple iCloud Keychain', 'icon' => 'apple'],
		'fbfc3007-154e-4ecc-8c0b-6e020557d7bd' => ['name' => 'Apple Passwords', 'icon' => 'apple'],

		// Google / Chrome
		'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4' => ['name' => 'Google Password Manager', 'icon' => 'google'],
		'adce0002-35bc-c60a-648b-0b25f1f05503' => ['name' => 'Chrome on Mac', 'icon' => 'google'],

		// Microsoft / Windows Hello
		'08987058-cadc-4b81-b6e1-30de50dcbe96' => ['name' => 'Windows Hello', 'icon' => 'windows'],
		'9ddd1817-af5a-4672-a2b9-3e3dd95000a9' => ['name' => 'Windows Hello', 'icon' => 'windows'],
		'6028b017-b1d4-4c02-b4b3-afcdafc96bb2' => ['name' => 'Windows Hello', 'icon' => 'windows'],
		'd3452668-01fd-4c12-926c-83a4204853aa' => ['name' => 'Microsoft Password Manager', 'icon' => 'windows'],

		// 1Password
		'bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password', 'icon' => '1password'],

		// Bitwarden
		'd548826e-79b4-db40-a3d8-11116f7e8349' => ['name' => 'Bitwarden', 'icon' => 'bitwarden'],

		// Dashlane
		'531126d6-e717-415c-9320-3d9aa6981239' => ['name' => 'Dashlane', 'icon' => 'dashlane'],
	];

	if(!empty($uuid) && isset($known[$uuid])){
		return $known[$uuid];
	}

	return [
		'name' => __('Security Key / Passkey', 'loginizer'),
		'icon' => 'default'
	];
}

