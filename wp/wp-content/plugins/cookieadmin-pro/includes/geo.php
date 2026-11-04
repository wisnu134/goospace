<?php

namespace CookieAdminPro;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Geo{

	static function get_location_details(){

		$ip = self::get_client_ip();
		if(empty($ip)){
			return null;
		}

		$location = self::geo_ip2country($ip);
		if(empty($location) || empty($location['country']) || empty($location['country']['iso_code'])){
			return null;
		}

		$geo_data = [
			'country_code' => $location['country']['iso_code'],
		];

		return $geo_data;
	}

	static function get_client_ip(){
		$ip = '';

		if(!empty($_SERVER['HTTP_CF_CONNECTING_IP'])){
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$forwarded = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
			$ips = explode(',', $forwarded);
			$ip = trim($ips[0]);
		}elseif(!empty($_SERVER['HTTP_X_REAL_IP'])){
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
		}elseif(!empty($_SERVER['REMOTE_ADDR'])){
			$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		if(filter_var($ip, FILTER_VALIDATE_IP)){
			return $ip;
		}

		return '';
	}

	static function geo_ip2country($ip){

		$db_data = get_option('cookieadmin_pro_geo_target_db_download', []);
		if(empty($db_data['filename'])){
			return false;
		}
		
		$wp_upload_dir_info = wp_upload_dir();
		$database_file = $wp_upload_dir_info['basedir'] . '/cookieadmin/' . $db_data['filename'];
		
		if(!file_exists($database_file)){
			return false;
		}

		// Include the autoloader for the MaxMind Database Reader
		$maxmind_autoloader = COOKIEADMIN_PRO_DIR . 'lib/MaxMind/autoloader.php';
		if(!file_exists($maxmind_autoloader)){
			return false;
		}

		include_once($maxmind_autoloader);

		$reader = null;
		$country = [];

		try{
			if(!class_exists('\CookieadminProMaxMind\Db\Reader')){
				return false;
			}

			$reader = new \CookieadminProMaxMind\Db\Reader($database_file);
			$country = $reader->get($ip);

		}catch(\CookieadminProMaxMind\Db\Reader\InvalidArgumentException $e){
			if(defined('WP_DEBUG') && WP_DEBUG){
				error_log('[Geo Targeting] : ' . $e->getMessage());
			}
			return false;

		}catch(\CookieadminProMaxMind\Db\Reader\InvalidDatabaseException $e){
			if(defined('WP_DEBUG') && WP_DEBUG){
				error_log('[Geo Targeting] : ' . $e->getMessage());
			}
			return false;

		}catch(\CookieadminProMaxMind\Db\Reader\AddressNotFoundException $e){
			return $country;

		}catch(\Exception $e){
			if(defined('WP_DEBUG') && WP_DEBUG){
				error_log('[Geo Targeting] : ' . $e->getMessage());
			}
			return false;
		}finally{
			if($reader instanceof \CookieadminProMaxMind\Db\Reader){
				$reader->close();
			}
		}

		return $country;
	}

	static function match_rule($country){

		$geo_enabled = get_option('cookieadmin_geo_enabled', false);
		if(empty($geo_enabled)){
			return null;
		}

		$rules = get_option('cookieadmin_geo_rules', []);

		if(empty($rules) || !is_array($rules)){
			return null;
		}

		if(empty($country)){
			return null;
		}

		$locations = include(COOKIEADMIN_PRO_DIR . 'lib/locations.php');
		$shortcuts = $locations['continents'];

		$shortcut_match  = null;
		$worldwide_match = null;

		// Priority -> Country -> Continent -> Wordlwide
		foreach($rules as $rule){
			if(empty($rule['countries']) || !is_array($rule['countries'])){
				continue;
			}
			foreach($rule['countries'] as $rule_country){
				// Highest priority: direct country match
				if($rule_country === $country){
					return $rule;
				}
				// Save first shortcut match
				if($shortcut_match === null && isset($shortcuts[$rule_country]) && in_array($country, $shortcuts[$rule_country], true)){
					$shortcut_match = $rule;
				}

				// Save first worldwide match
				if($worldwide_match === null && $rule_country === 'worldwide'){
					$worldwide_match = $rule;
				}
			}
		}

		$return = !empty($shortcut_match) ? $shortcut_match : $worldwide_match;

		return $return;
	}

	static function get_active_law($default_law){

		$geo_enabled = get_option('cookieadmin_geo_enabled', false);
		if(empty($geo_enabled) || is_admin()){
			return $default_law;
		}

		$location = self::get_location_details();

		if(empty($location) || empty($location['country_code'])){
			return $default_law;
		}

		$rule = self::match_rule($location['country_code']);
		
		if(!empty($rule) && !empty($rule['law'])){
			return $rule['law'];
		} else if(!empty($rule) && empty($rule['law'])){
			return '';
		}

		return $default_law;
	}

	static function get_feature_overrides(){

		$inherit_defaults = [
			'block_scripts' => 'inherit',
			'content_blocking' => 'inherit',
			'google_consent_mode_v2' => 'inherit',
			'clarity_consent' => 'inherit',
		];

		$geo_enabled = get_option('cookieadmin_geo_enabled', false);
		if(empty($geo_enabled)){
			return $inherit_defaults;
		}

		$location = self::get_location_details();
		if(empty($location) || empty($location['country_code'])){
			return $inherit_defaults;
		}

		$rule = self::match_rule($location['country_code']);
		if(empty($rule)){
			return $inherit_defaults;
		}

		return [
			'active_law' => !empty($rule['law']) ? $rule['law'] : '',
			'block_scripts' => !empty($rule['block_scripts']) ? $rule['block_scripts'] : 'inherit',
			'content_blocking' => !empty($rule['content_blocking']) ? $rule['content_blocking'] : 'inherit',
			'google_consent_mode_v2' => !empty($rule['google_consent_mode_v2']) ? $rule['google_consent_mode_v2'] : 'inherit',
			'clarity_consent' => !empty($rule['clarity_consent']) ? $rule['clarity_consent'] : 'inherit',
			'country_code' => !empty($location['country_code']) ? $location['country_code'] : '',
		];
	}

	static function apply_geo_rule_settings($cookieadmin_settings){

		$geo_enabled = get_option('cookieadmin_geo_enabled', false);
		if(empty($geo_enabled) || is_admin()){
			return $cookieadmin_settings;
		}

		$geo_overrides = self::get_feature_overrides();
		if(empty($geo_overrides['country_code'])){
			return $cookieadmin_settings;
		}
	    
		if(array_key_exists('active_law', $geo_overrides)){
			unset($geo_overrides['active_law']);
		}
		
		if(array_key_exists('country_code', $geo_overrides)){
			unset($geo_overrides['country_code']);
		}
		
		if(empty($cookieadmin_settings) || !is_array($cookieadmin_settings)){
			$cookieadmin_settings = [];
		}
		
		foreach($geo_overrides as $key => $val){
			if($geo_overrides[$key] === 'off'){
				$cookieadmin_settings[$key] = false;
			}else if($geo_overrides[$key] === 'on'){
				$cookieadmin_settings[$key] = true;
			}
		}
		
		return $cookieadmin_settings;
	}
}
