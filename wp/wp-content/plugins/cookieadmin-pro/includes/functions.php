<?php
/*
* CookieAdmin
* https://cookieadmin.net
* (c) Softaculous Team
*/

// Are we being accessed directly ?
if(!defined('COOKIEADMIN_PRO_VERSION')) {
	exit('Hacking Attempt !');
}

function cookieadmin_pro_activation(){
	update_option('cookieadmin_pro_version', COOKIEADMIN_PRO_VERSION);
	update_option('cookieadmin_auto_scan', COOKIEADMIN_PRO_VERSION);
	
	// Commented as it is a class
	// include_once(COOKIEADMIN_PRO_DIR . 'includes/database.php');
	\CookieAdminPro\Database::activate();
	
	return true;
	
}

function cookieadmin_pro_deactivation(){
	delete_option('cookieadmin_pro_version');
	
	wp_clear_scheduled_hook('cookieadmin_run_auto_cookie_scan');
	wp_clear_scheduled_hook('cookieadmin_daily_consent_log_pruning');
	wp_clear_scheduled_hook('cookieadmin_daily_geo_db_update_check');
}

function cookieadmin_pro_is_network_active($plugin_name){
	$is_network_wide = false;
	
	// Handling network site
	if(!is_multisite()){
		return $is_network_wide;
	}
	
	$_tmp_plugins = get_site_option('active_sitewide_plugins');

	if(!empty($_tmp_plugins) && preg_grep('/.*\/'.$plugin_name.'\.php$/', array_keys($_tmp_plugins))){
		$is_network_wide = true;
	}
	
	return $is_network_wide;
}

function cookieadmin_pro_update_checker(){
	
	$current_version = get_option('cookieadmin_pro_version', '0.0');
	$version = (int) str_replace('.', '', $current_version);

	// No update required
	if($current_version == COOKIEADMIN_PRO_VERSION){
		return true;
	}
	
	$is_network_wide = cookieadmin_pro_is_network_active('cookieadmin');
	
	if($is_network_wide){
		$free_ins = get_site_option('cookieadmin_free_installed');
	}else{
		$free_ins = get_option('cookieadmin_free_installed');
	}
	
	// If plugin runing reached here it means CookieAdmin free installed 
	if(empty($free_ins)){
		if($is_network_wide){
			update_site_option('cookieadmin_free_installed', time());
		}else{
			update_option('cookieadmin_free_installed', time());
		}
	}
	
	update_option('cookieadmin_version_pro_nag', time());
	update_option('cookieadmin_version_free_nag', time());
	update_option('cookieadmin_pro_version', COOKIEADMIN_PRO_VERSION);
}


// Load license data
function cookieadmin_pro_load_license($parent = 0){
	
	global $cookieadmin, $lic_resp, $sitepad;
	
	$license_field = 'cookieadmin_license';
	$license_api_url = COOKIEADMIN_API;
	
	// Save license
	if(!empty($parent) && is_string($parent) && strlen($parent) > 5){		
		$lic['license'] = $parent;
		delete_transient('cookieadmin_license_check_retry');
	
	// Load license of Soft Pro
	}elseif(!empty($parent)){
		$license_field = 'softaculous_pro_license';
		$lic = get_option('softaculous_pro_license', []);
	
	// My license
	}else{
		$lic = get_option($license_field, []);
	}
	
	// Loaded license is a Soft Pro
	if(!empty($lic['license']) && preg_match('/^softwp/is', $lic['license'])){
		$license_field = 'softaculous_pro_license';
		$license_api_url = 'https://a.softaculous.com/softwp/';
		$prods = apply_filters('softaculous_pro_products', []);
	}else{
		$prods = [];
	}

	if(empty($lic['last_update'])){
		$lic['last_update'] = time() - 86600;
	}
	
	// transient for ratelimit.
	$retry = (int) get_transient('cookieadmin_license_check_retry');
	
	// Update license details as well
	if(!empty($lic) && !empty($lic['license']) && (time() - @$lic['last_update']) >= 86400 && $retry < 3){
		
		$url = $license_api_url.'/license.php?license='.$lic['license'].'&prods='.implode(',', $prods).'&url='.rawurlencode(site_url());
		$resp = wp_remote_get($url);
		$lic_resp = $resp;
		
		$retry++; // updating the retry
		set_transient('cookieadmin_license_check_retry', $retry, 80000);

		//Did we get a response ?
		if(is_array($resp)){
			
			$tosave = json_decode($resp['body'], true);
			
			//Is it the license ?
			if(!empty($tosave['license'])){
				$tosave['last_update'] = time();
				update_option($license_field, $tosave);
				$lic = $tosave;
				delete_transient('cookieadmin_license_check_retry');
			}
		}
	}
	
	// If the license is Free or Expired check for Softaculous Pro license
	if(empty($lic) || empty($lic['active'])){
		
		if(function_exists('softaculous_pro_load_license')){
			$softaculous_license = softaculous_pro_load_license();
			if(!empty($softaculous_license['license']) && 
				(!empty($softaculous_license['active']) || empty($lic['license']))
			){
				$lic = $softaculous_license;
			}
		}elseif(empty($parent)){
			$soft_lic = get_option('softaculous_pro_license', []);
			
			if(!empty($soft_lic)){
				return cookieadmin_pro_load_license(1);
			}
		}
	}
	
	if(!empty($lic['license'])){
		$cookieadmin['license'] = $lic;
	}

	// For sitepad users
	if(defined('SITEPAD') && empty($cookieadmin['license'])){
		$license = !empty($sitepad['license']) ? $sitepad['license']: (isset($sitepad['server_license']) ? $sitepad['server_license'] : []);
		$license['active'] = isset($license['active']) ? $license['active'] : (isset($license['status']) ? $license['status'] : '');
		$cookieadmin['license'] = $license;
	}
	
}

add_filter('softaculous_pro_products', 'cookieadmin_pro_softaculous_pro_products', 10, 1);
function cookieadmin_pro_softaculous_pro_products($r = []){
	$r['cookieadmin'] = 'cookieadmin';
	return $r;
}

// Add our license key if ANY
function cookieadmin_pro_updater_filter_args($queryArgs){
	
	global $cookieadmin;
	
	if (!empty($cookieadmin['license']['license'])){
		$queryArgs['license'] = $cookieadmin['license']['license'];
	}
	
	$queryArgs['url'] = rawurlencode(site_url());
	
	return $queryArgs;
}

function cookieadmin_pro_ajax_handler(){
	
	$cookieadmin_fn = (!empty($_REQUEST['cookieadmin_act']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_act'])) : '');
	
	if(empty($cookieadmin_fn)){
		wp_send_json_error(array('message' => 'Action not posted'));
	}
	
	// Define a whitelist of allowed functions
	$user_allowed_actions = array(
		'save_consent' => 'save_consent'
	);
	
	$admin_allowed_actions = array(
		'add_cookie' => 'add_cookie',
		'export_logs' => 'export_logs',
		'get_consent_logs' => 'get_consent_logs',
		'dismiss_expired_licenses' => 'dismiss_expired_licenses',
		'version_notice' => 'version_notice',
		'purne_consents' => 'consent_log_pruning_ajax',
		'create_consent_table' => 'create_consent_table',
		'search_scan_content' => 'ajax_search_scan_content',
		'save_scan_pages' => 'ajax_save_scan_pages',
	);

	$do_not_sell_allowed_actions = array(
		'submit_do_not_sell_form' => 'submit_do_not_sell_form',
		'do_not_sell_requests_pagination' => 'do_not_sell_requests_pagination',
		'generate_do_not_sell_page' => 'generate_do_not_sell_page',
		'export_dns_requests' => 'export_dns_requests'
	);

	$dpdp_allowed_actions = array(
		'submit_dpdp_request' => 'submit_dpdp_request',
		'generate_dpdp_page' => 'generate_dpdp_page',
		'export_dpdp_requests' => 'export_dpdp_requests'
	);

	$geo_admin_actions = array(
		'save_geo_settings' => 'save_geo_settings',
		'add_default_rules' => 'add_default_rules_ajax',
		'toggle_geo_enabled' => 'toggle_geo_enabled',
		'download_geo_target_country_db' => 'download_geo_target_country_db',
		'close_geo_db_update_notice' => 'close_geo_db_update_notice',
	);
	
	if(array_key_exists($cookieadmin_fn, $user_allowed_actions)){
		//commented due to cache issue. 
		// check_ajax_referer('cookieadmin_pro_js_nonce', 'cookieadmin_pro_security');
		header_remove('Set-Cookie');
		call_user_func('\CookieAdminPro\Enduser::'.$user_allowed_actions[$cookieadmin_fn]);
		
	}elseif(array_key_exists($cookieadmin_fn, $admin_allowed_actions)){
		
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
	 
		if(!current_user_can('administrator')){
			wp_send_json_error(array('message' => 'Sorry, but you do not have permissions to perform this action'));
		}
		
		call_user_func('\CookieAdminPro\Admin::'.$admin_allowed_actions[$cookieadmin_fn]);
		
	}elseif(array_key_exists($cookieadmin_fn, $do_not_sell_allowed_actions)){
		if($cookieadmin_fn !== 'submit_do_not_sell_form'){
			check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
			if(!current_user_can('administrator')){
				wp_send_json_error(array('message' => 'Sorry, but you do not have permissions to perform this action'));
			}
			
			$dns_class = 'Admin\\DoNotSell::';
		}else{
			check_ajax_referer('cookieadmin_pro_js_nonce', 'cookieadmin_pro_security');
			$dns_class = 'DoNotSell::';
		}
		call_user_func('\CookieAdminPro\\'.$dns_class.$do_not_sell_allowed_actions[$cookieadmin_fn]);

	}elseif(array_key_exists($cookieadmin_fn, $dpdp_allowed_actions)){
		if($cookieadmin_fn !== 'submit_dpdp_request'){
			check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
			if(!current_user_can('administrator')){
				wp_send_json_error(array('message' => 'Sorry, but you do not have permissions to perform this action'));
			}

			$dpdp_class = 'Admin\DPDP::';
		}else{
			check_ajax_referer('cookieadmin_pro_js_nonce', 'cookieadmin_pro_security');
			$dpdp_class = 'DPDP::';
		}
		call_user_func('\CookieAdminPro\\'.$dpdp_class.$dpdp_allowed_actions[$cookieadmin_fn]);

	}elseif(array_key_exists($cookieadmin_fn, $geo_admin_actions)){
		
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
	 
		if(!current_user_can('administrator')){
			wp_send_json_error(array('message' => 'Sorry, but you do not have permissions to perform this action'));
		}
		
		call_user_func('\CookieAdminPro\Admin\Geo::'.$geo_admin_actions[$cookieadmin_fn]);
		
	}
	
	else{
		wp_send_json_error(array('message' => 'Unauthorized action'));
	}
	
}

// Handle the Check for update link and ask to install license key
function cookieadmin_pro_updater_check_link($final_link){
	
	global $cookieadmin;
	
	if(empty($cookieadmin['license']['license'])){
		return '<a href="'.admin_url('admin.php?page=cookieadmin-license').'">'.esc_html__('Install CookieAdmin Pro License Key', 'cookieadmin').'</a>';
	}
	
	return $final_link;
}

// Prevent update of cookieadmin free
function cookieadmin_pro_get_free_version_num(){
		
	if(defined('COOKIEADMIN_VERSION')){
		return COOKIEADMIN_VERSION;
	}
	
	// In case of cookieadmin deactive
	return cookieadmin_pro_file_get_version_num('cookieadmin/cookieadmin.php');
}

// Prevent update of cookieadmin free
function cookieadmin_pro_file_get_version_num($plugin){
	
	// In case of cookieadmin deactive
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/'.$plugin);
	
	if(empty($plugin_data)){
		return false;
	}
	
	return $plugin_data['Version'];
	
}

// Keep the free and Pro plugins on the same version: only show an update
// when both plugins end up on that same version. This also keeps a Pro
// update from being offered ahead of the free one, which is the source of
// truth on wp.org and subject to a 24-hour review delay.
// https://wordpress.org/news/2026/06/pts/
// Intentionally this also holds the free plugin back when the Pro license is
// inactive: the Pro server then offers no update, and letting the free plugin
// move on alone would break compatibility between the two.
function cookieadmin_pro_disable_manual_update_for_plugin($transient){
	$free = 'cookieadmin/cookieadmin.php';
	$pro = 'cookieadmin-pro/cookieadmin-pro.php';

	if(!is_object($transient) || (empty($transient->response[$free]) && empty($transient->response[$pro]))){
		return $transient;
	}

	// Some object caches (e.g. LiteSpeed) hand back the cached instance itself,
	// so work on a copy or our changes leak into the cache and get re-filtered.
	$transient = clone $transient;

	// While the post-Pro-upgrade handler runs, COOKIEADMIN_PRO_VERSION is the pre-upgrade value.
	$upgrading = !empty($GLOBALS['cookieadmin_pro_is_upgraded']);
	$pro_version = $upgrading ? cookieadmin_pro_file_get_version_num($pro) : COOKIEADMIN_PRO_VERSION;
	$free_version = cookieadmin_pro_get_free_version_num();

	// Where each plugin lands: its offered update, or its installed version if none.
	$free_target = !empty($transient->response[$free]->new_version) ? $transient->response[$free]->new_version : $free_version;
	$pro_target = !empty($transient->response[$pro]->new_version) ? $transient->response[$pro]->new_version : $pro_version;

	// A version we cannot read means we cannot keep the two in step. If the free
	// plugin is the missing one, updating Pro is pointless anyway: it cannot run
	// without the free plugin.
	if(empty($free_target) || empty($pro_target)){
		unset($transient->response[$free], $transient->response[$pro]);
		return $transient;
	}

	// An update is allowed only if it lands the plugin where the other one is now,
	// or where the other one is heading. That lets a plugin left behind catch up
	// to the other, and blocks anything that would push one ahead of the other.
	if(version_compare($free_target, $pro_target, '!=') && version_compare($free_target, $pro_version, '!=')){
		unset($transient->response[$free]);
	}

	if(version_compare($pro_target, $free_target, '!=') && version_compare($pro_target, $free_version, '!=')){
		unset($transient->response[$pro]);
	}

	// Both have an update: show only Pro, it updates the free plugin once done.
	if(isset($transient->response[$free], $transient->response[$pro])){
		unset($transient->response[$upgrading ? $pro : $free]);
	}

	if(isset($transient->response[$free]) && is_object($transient->response[$free])){
		$transient->response[$free] = clone $transient->response[$free];
		$transient->response[$free]->new_version = $pro_version;
		$transient->response[$free]->package = 'https://downloads.wordpress.org/plugin/cookieadmin.'.$pro_version.'.zip';
	}

	return $transient;
}

// Auto update free version after update pro version
function cookieadmin_pro_update_free_after_pro($upgrader_object, $options){
	
	// Check if the action is an update for the plugins
	if($options['action'] != 'update' || $options['type'] != 'plugin'){
		return;
	}
		
	// Define the slugs for the free and pro plugins
	$free_slug = 'cookieadmin/cookieadmin.php'; 
	$pro_slug = 'cookieadmin-pro/cookieadmin-pro.php';

	// Check if the pro plugin is in the list of updated plugins
	if( 
		(isset($options['plugins']) && in_array($pro_slug, $options['plugins']) && !in_array($free_slug, $options['plugins'])) ||
		(isset($options['plugin']) && $pro_slug == $options['plugin'])
	){
	
		// Trigger the update for the free plugin
		$current_version = cookieadmin_pro_get_free_version_num();
		
		if(empty($current_version)){
			return;
		}
		
		$GLOBALS['cookieadmin_pro_is_upgraded'] = true;
		
		// This will set the 'update_plugins' transient again
		wp_update_plugins();

		// Check for updates for the free plugin
		$update_plugins = get_site_transient('update_plugins');
		
		if(empty($update_plugins) || !isset($update_plugins->response[$free_slug]) || version_compare($update_plugins->response[$free_slug]->new_version, $current_version, '<=')){
			return;
		}
		
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
		
		$skin = wp_doing_ajax()? new WP_Ajax_Upgrader_Skin() : null;
		
		$upgrader = new Plugin_Upgrader($skin);
		$upgraded = $upgrader->upgrade($free_slug);
		
		if(!is_wp_error($upgraded) && $upgraded){
			// Re-active free plugins
			if( file_exists( WP_PLUGIN_DIR . '/'.  $free_slug ) && is_plugin_inactive($free_slug) ){
				activate_plugin($free_slug); // TODO for network
			}
			
			// Re-active pro plugins
			if( file_exists( WP_PLUGIN_DIR . '/'.  $pro_slug ) && is_plugin_inactive($pro_slug) ){
				activate_plugin($pro_slug); // TODO for network
			}
		}
	}
}

function cookieadmin_pro_api_url($main_server = 0, $suffix = 'cookieadmin'){
	
	global $cookieadmin;
	
	$r = array(
		'https://s0.softaculous.com/a/softwp/',
		'https://s1.softaculous.com/a/softwp/',
		'https://s2.softaculous.com/a/softwp/',
		'https://s3.softaculous.com/a/softwp/',
		'https://s4.softaculous.com/a/softwp/',
		'https://s5.softaculous.com/a/softwp/',
		'https://s6.softaculous.com/a/softwp/',
		'https://s7.softaculous.com/a/softwp/',
		'https://s8.softaculous.com/a/softwp/'
	);
	
	$mirror = $r[array_rand($r)];
	
	if(!empty($suffix)){
		$mirror = str_replace('/softwp', '/'.$suffix, $mirror);
	}
	
	if(!empty($main_server) && $main_server < 0){
		return $mirror;
	}
	
	// If the license is newly issued, we need to fetch from API only
	if(!empty($main_server) || empty($cookieadmin['license']['last_edit']) || 
		(!empty($cookieadmin['license']['last_edit']) && (time() - 3600) < $cookieadmin['license']['last_edit'])
	){
		$mirror = COOKIEADMIN_API;
	}
	
	return $mirror;
	
}

function cookieadmin_pro_plugins_expired($plugins){
	$plugins[] = 'CookieAdmin';
	return $plugins;
}

function cookieadmin_pro_expiry_notice(){
	global $cookieadmin;

	// The combined notice for all Softaculous plugin to show that the license has expired
	$dismissed_at = get_option('softaculous_expired_licenses', 0);
	$expired_plugins = apply_filters('softaculous_expired_licenses', []);
	if(
		!empty($expired_plugins) && 
		is_array($expired_plugins) && 
		!defined('SOFTACULOUS_EXPIRY_LICENSES') && 
		(empty($dismissed_at) || ($dismissed_at + WEEK_IN_SECONDS) < time())
	){

		define('SOFTACULOUS_EXPIRY_LICENSES', true); // To make sure other plugins don't return a Notice
		
		echo '<div class="notice notice-error is-dismissible" id="cookieadmin-pro-expiry-notice">
				<p>'.
				/* translators: 1: Styling for red color and bold, 2: Styling for red color and bold ends, 3: List of Softaculous plugins that have expired */
				sprintf(esc_html__('Your SoftWP license has %1$sexpired%2$s. Please renew it to continue receiving uninterrupted updates and support for %3$s.', 'cookieadmin'),
				'<font style="color:red;"><b>',
				'</b></font>',
				esc_html(implode(', ', $expired_plugins))
				). '</p>
			</div>';

		wp_register_script('cookieadmin-pro-expiry-notice', '', ['jquery'], COOKIEADMIN_PRO_VERSION, true);
		wp_enqueue_script('cookieadmin-pro-expiry-notice');
		wp_add_inline_script('cookieadmin-pro-expiry-notice', '
		jQuery(document).ready(function(){
			jQuery("#cookieadmin-pro-expiry-notice").on("click", ".notice-dismiss", function(e){
				e.preventDefault();
				let target = jQuery(e.target);

				let jEle = target.closest("#cookieadmin-pro-expiry-notice");
				jEle.slideUp();

				jQuery.post("'.admin_url('admin-ajax.php').'", {
					cookieadmin_pro_security : "'.wp_create_nonce('cookieadmin_pro_admin_js_nonce').'",
					action: "cookieadmin_pro_ajax_handler",
					cookieadmin_act: "dismiss_expired_licenses",
				}, function(res){
					if(!res["success"]){
						alert(res["data"]);
					}
				}).fail(function(data){
					alert("There seems to be some issue dismissing this alert");
				});
			});
		})');
	}
}

function cookieadmin_pro_human_readable_time($timestamp){
	
	$now = time();
	$today_start = strtotime('today');
	$yesterday_start = strtotime('yesterday');

	if ($timestamp >= $today_start) {
		return 'Today ' . wp_date('g:i A T', $timestamp);
	} elseif ($timestamp >= $yesterday_start) {
		return 'Yesterday ' . wp_date('g:i A T', $timestamp);
	} else {
		return wp_date('M j Y g:i A T', $timestamp); // e.g., Dec 6 2024 6:00 AM UTC
	}
}

function cookieadmin_pro_scan_cookies($urls){
	return call_user_func('\CookieAdminPro\Scanner::start_scan', $urls);
}

function cookieadmin_pro_cron_schedules($schedules){
	
	$schedules['cookieadmin_every_month'] = array(
			'interval' => DAY_IN_SECONDS * 30,
			'display'  => 'Once a Month'
		);
		return $schedules;
}

// Currently sending only single url - later will be a batch
function cookieadmin_pro_get_remaining_urls($urls){
	
	$to_scan_urls = get_option('cookieadmin_to_scan_urls', []);
	
	// NOTE: Treat attempted URLs as scanned upon successful response
	$scanned_urls = $urls;
	$remaining_urls = [];
	
	if(!empty($to_scan_urls) && !empty($scanned_urls)){
		
		$to_scan = array_values(array_unique(array_map('untrailingslashit', $to_scan_urls)));
		$scanned = array_values(array_unique(array_map('untrailingslashit', $scanned_urls)));
		
		$to_scan_urls = array_values(array_diff($to_scan, $scanned));
		
		update_option('cookieadmin_to_scan_urls', $to_scan_urls);
		
		$remaining_urls = array_slice($to_scan_urls, 0, 1);
	}
	
	return $remaining_urls;
}

function cookieadmin_pro_update_scan_count($res){
	
	$ck_scan = get_option('cookieadmin_scan');
	$count = !empty($ck_scan['count']) ? $ck_scan['count'] : 0;
	$count = $count + array_sum(array_values($res));
	
	update_option('cookieadmin_scan', [
		'status' => 2,
		'success' => true,
		'count' => $count,
		'update' => time()
	]);
}

function cookieadmin_pro_defaults(){
	global $cookieadmin;
	
	// TODO:: Update the GPC messages inside the default index as well.
	$cookieadmin['gpc_message_default'] = __('GPC signal honored', 'cookieadmin');
	$cookieadmin['gpc_override_warning_default'] = __('I understand that Global Privacy Control (GPC) will be overridden and I allow this site to apply my selected consent preferences.', 'cookieadmin');
	
	$dns_settings = get_option('cookieadmin_do_not_sell', []);
	if(
		!empty($dns_settings) && 
		!empty($dns_settings['enabled']) && 
		!empty($dns_settings['selected_page'])
	){
		$cookieadmin['default']['dns_fname'] = __('First Name', 'cookieadmin');
		$cookieadmin['default']['dns_lname'] = __('Last Name', 'cookieadmin');
		$cookieadmin['default']['dns_confirm_msg'] = __('I confirm that the information I have provided is correct and I want to opt out of the selling or sharing of my personal information.', 'cookieadmin');
		$cookieadmin['default']['dns_submit'] = __('Submit Request', 'cookieadmin');
		$cookieadmin['default']['dns_phone'] = __('Phone Number', 'cookieadmin');
		$cookieadmin['default']['dns_zip'] = __('ZIP/Postal code', 'cookieadmin');
		$cookieadmin['default']['dns_email'] = __('Email', 'cookieadmin');
		$cookieadmin['default']['dns_heading'] = __('Do Not Sell or Share My Personal Information', 'cookieadmin');
		$cookieadmin['default']['dns_confirm_err'] = __('Please confirm information before submit', 'cookieadmin');
		$cookieadmin['default']['dns_empty_fields_err'] = __('Fill all the required fields', 'cookieadmin');
		$cookieadmin['default']['dns_name_err'] = __('Name should contain only alphabets and spaces', 'cookieadmin');
		$cookieadmin['default']['dns_email_err'] = __('Enter a valid email address', 'cookieadmin');
		$cookieadmin['default']['dns_zip_err'] = __('Enter a valid ZIP/Postal code', 'cookieadmin');
		$cookieadmin['default']['dns_phone_err'] = __('Enter a valid Phone number', 'cookieadmin');
		$cookieadmin['default']['dns_request_in_progress'] = __('A request is already in progress for this email address', 'cookieadmin');
		$cookieadmin['default']['dns_submission_err'] = __('Request submission failed', 'cookieadmin');
		$cookieadmin['default']['dns_submission_success'] = __('Your request has been submitted successfully...', 'cookieadmin');
	}

	$dpdp_settings = get_option('cookieadmin_dpdp_settings', []);
	if(
		get_option('cookieadmin_law') === 'cookieadmin_dpdp' ||
		(!empty($dpdp_settings) && !empty($dpdp_settings['enabled']))
	){
		$cookieadmin['default']['dpdp_heading'] = __('Exercise Your Data Principal Rights (DPDP Act)', 'cookieadmin');
		$cookieadmin['default']['dpdp_fname'] = __('First Name', 'cookieadmin');
		$cookieadmin['default']['dpdp_lname'] = __('Last Name', 'cookieadmin');
		$cookieadmin['default']['dpdp_email'] = __('Email', 'cookieadmin');
		$cookieadmin['default']['dpdp_request_type'] = __('Request Type', 'cookieadmin');
		$cookieadmin['default']['dpdp_details'] = __('Details of your request (optional)', 'cookieadmin');
		$cookieadmin['default']['dpdp_confirm_msg'] = __('I confirm that the information I have provided is correct and I want to submit this request under the DPDP Act, 2023.', 'cookieadmin');
		$cookieadmin['default']['dpdp_submit'] = __('Submit Request', 'cookieadmin');
		$cookieadmin['default']['dpdp_confirm_err'] = __('Please confirm the information before submitting', 'cookieadmin');
		$cookieadmin['default']['dpdp_empty_fields_err'] = __('Fill all the required fields', 'cookieadmin');
		$cookieadmin['default']['dpdp_name_err'] = __('Name should contain only alphabets and spaces', 'cookieadmin');
		$cookieadmin['default']['dpdp_email_err'] = __('Enter a valid email address', 'cookieadmin');
		$cookieadmin['default']['dpdp_phone_err'] = __('Enter a valid phone number', 'cookieadmin');
		$cookieadmin['default']['dpdp_type_err'] = __('Select a valid request type', 'cookieadmin');
		$cookieadmin['default']['dpdp_request_in_progress'] = __('A similar request is already in progress for this email address', 'cookieadmin');
		$cookieadmin['default']['dpdp_submission_err'] = __('Request submission failed', 'cookieadmin');
		$cookieadmin['default']['dpdp_submission_success'] = __('Your request has been submitted successfully. We will respond within the published timeline.', 'cookieadmin');
		$cookieadmin['default']['dpdp_disabled_err'] = __('This service is currently unavailable', 'cookieadmin');
		$cookieadmin['default']['dpdp_exercise_rights'] = __('Exercise your Data Principal rights', 'cookieadmin');
	}
}

// Check if polylang is installed and active
function cookieadmin_is_multilingual_active(){
	return (function_exists('pll_register_string') && function_exists('pll__') || defined('ICL_SITEPRESS_VERSION'));
}

function cookieadmin_is_polylang_active(){
	return function_exists('pll_register_string') && function_exists('pll__');
}

function cookieadmin_is_wpml_active(){
	return defined('ICL_SITEPRESS_VERSION');
}

// Should only be used for strings which need to be shown to end user, this is to support multilingual plugins
function cookieadmin__($string, $key){
	
	if(!cookieadmin_is_multilingual_active()){
		return $string;
	}
	
	return \CookieAdminPro\TranslateString::string($string, $key);
	
}