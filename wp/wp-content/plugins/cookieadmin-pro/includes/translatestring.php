<?php

namespace CookieAdminPro;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class TranslateString {

	// Register strings to translate
	static function register_strings(){

		global $wpdb, $cookieadmin;
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');

		// Strings saved in the options table
		$policy = cookieadmin_load_policy();
		$law = get_option('cookieadmin_law', 'cookieadmin_gdpr');

		// Do not show any message if banner is off via Geo rule
		if(empty($law)){
			return;
		}

		// String keys to translate
		$strings_to_translate = [
			'cookieadmin_notice_title',
			'cookieadmin_notice',
			'cookieadmin_preference_title',
			'cookieadmin_preference',
			'reConsent_title',
			'cookieadmin_customize_btn',
			'cookieadmin_reject_btn',
			'cookieadmin_accept_btn',
			'cookieadmin_save_btn',
			'powered_by',
			'reconsent',
			'cookie_preferences',
			'remark_standard',
			'none',
			'necessary_cookies',
			'necessary_cookies_desc',
			'functional_cookies',
			'functional_cookies_desc',
			'analytical_cookies',
			'analytical_cookies_desc',
			'advertisement_cookies',
			'advertisement_cookies_desc',
			'unclassified_cookies',
			'unclassified_cookies_desc',
		];

		// Register GPC messages
		if(!empty($cookieadmin['gpc_message_default']) && !empty($cookieadmin['gpc_override_warning_default'])){
			if(cookieadmin_is_polylang_active()){
				pll_register_string('gpc_message_default', $cookieadmin['gpc_message_default'], 'CookieAdmin');
				pll_register_string('gpc_override_warning_default', $cookieadmin['gpc_override_warning_default'], 'CookieAdmin', true);
			}else if(cookieadmin_is_wpml_active()){
				do_action('wpml_register_single_string', 'CookieAdmin', 'gpc_message_default', $cookieadmin['gpc_message_default']);
				do_action('wpml_register_single_string', 'CookieAdmin', 'gpc_override_warning_default', $cookieadmin['gpc_override_warning_default']);
			}
		}

		foreach($policy[$law] as $key => $value){
			$multine_strings = [
				'cookieadmin_notice_title',
				'cookieadmin_notice',
				'cookieadmin_preference_title',
				'cookieadmin_preference',
			];
			if(in_array($key, $strings_to_translate)){
				if(cookieadmin_is_polylang_active()){
					pll_register_string($key, $value, 'CookieAdmin', in_array($key, $multine_strings));	
				}else if(cookieadmin_is_wpml_active()){
					do_action('wpml_register_single_string', 'CookieAdmin', $key, $value);	
				}
			}
		}

		// Strings for the cookie category and desc for consent banner
		$banner_strings = cookieadmin_load_strings($policy[$law]);
		foreach($banner_strings as $key => $value){
			if(in_array($key, $strings_to_translate)){
				if(cookieadmin_is_polylang_active()){
					pll_register_string($key, $value, 'CookieAdmin');
				}else if(cookieadmin_is_wpml_active()){
					do_action('wpml_register_single_string', 'CookieAdmin', $key, $value);
				}
			}
		}

		// Translate cookieadmin categories saved in the database is exist any
		$cookies = $wpdb->get_results("SELECT cookie_name, category, expires, description, patterns FROM {$table_name}");
		if(!empty($cookies)){
			foreach($cookies as $cookie){
				if(!empty($cookie->description)){
					if(cookieadmin_is_polylang_active()){
						pll_register_string($cookie->cookie_name, $cookie->description, 'CookieAdmin');
					}else if(cookieadmin_is_wpml_active()){
						do_action('wpml_register_single_string', 'CookieAdmin', $cookie->cookie_name, $cookie->description);
					}
				}
			}
		}

		// Translate language strings localized to js
		$language_strings = [
			'show_less' => 'Show less',
			'duration' => 'Duration',
			'session' => 'Session',
			'days' => 'Days',
			'gpc_alert' => 'Please accept override GPC before saving preference.',
			'gpc_alert_load_content' => 'Please accept override GPC from consent preferences to load this content.',
		];
		foreach($language_strings as $key => $value){
			if(cookieadmin_is_polylang_active()){
				pll_register_string($key, $value, 'CookieAdmin');
			}else if(cookieadmin_is_wpml_active()){
				do_action('wpml_register_single_string', 'CookieAdmin', $key, $value);
			}
		}
		
		// Default language strings
		if(!empty($cookieadmin['default'])){
			foreach($cookieadmin['default'] as $key => $defaults){
				if(cookieadmin_is_polylang_active()){
					pll_register_string($key, $defaults, 'CookieAdmin');
				}else if(cookieadmin_is_wpml_active()){
					do_action('wpml_register_single_string', 'CookieAdmin', $key, $defaults);
				}
			}
		}
		
	}

	static function translate_strings($strings = []){

		if(!cookieadmin_is_multilingual_active()){
			return $strings;
		}

		// Translate languages
		if(!empty($strings['lang'])){
			if(cookieadmin_is_polylang_active()){
				$strings['lang'] = map_deep($strings['lang'], 'pll__');
			}else if(cookieadmin_is_wpml_active()){
				foreach($strings['lang'] as $key => $value){
					$strings[$key] = apply_filters( 'wpml_translate_single_string', $value, 'CookieAdmin', $key);
				}
			}
		}

		// Translate banner strings
		$banner_strings = [
			'cookieadmin_notice_title',
			'cookieadmin_notice',
			'cookieadmin_preference_title',
			'cookieadmin_preference',
			'cookieadmin_customize_btn',
			'cookieadmin_reject_btn',
			'cookieadmin_accept_btn',
			'cookieadmin_save_btn'
		];
		foreach($banner_strings as $key){
			if(!empty($strings[$key])){
				if(cookieadmin_is_polylang_active()){
					$strings[$key] = pll__($strings[$key]);
				}else if(cookieadmin_is_wpml_active()){
					$strings[$key] = apply_filters( 'wpml_translate_single_string', $strings[$key], 'CookieAdmin', $key);
				}
			}
		}

		// Translate categorized cookies
		if(!empty($strings['categorized_cookies'])){
			foreach($strings['categorized_cookies'] as $index => $cookie){
				
				if(!empty($cookie->description)){
					if(cookieadmin_is_polylang_active()){
						$strings['categorized_cookies'][$index]->description = pll__($cookie->description);
					}else if(cookieadmin_is_wpml_active()){
						$strings['categorized_cookies'][$index]->description = apply_filters( 'wpml_translate_single_string', $cookie->description, 'CookieAdmin', $cookie->cookie_name);
					}
				}
			}
		}

		$exclude = [
			'powered_by',
			'reconsent',
			'cookie_preferences',
			'remark_standard',
			'none',
			'necessary_cookies',
			'necessary_cookies_desc',
			'functional_cookies',
			'functional_cookies_desc',
			'analytical_cookies',
			'analytical_cookies_desc',
			'advertisement_cookies',
			'advertisement_cookies_desc',
			'unclassified_cookies',
			'unclassified_cookies_desc',
			'gpc_message',
			'gpc_alert',
			'gpc_alert_load_content',
		];

		foreach($exclude as $key){
			if(!empty($strings[$key])){
				if(cookieadmin_is_polylang_active()){
					$strings[$key] = pll__($strings[$key]);
				}else if(cookieadmin_is_wpml_active()){
					$strings[$key] = apply_filters( 'wpml_translate_single_string', $strings[$key], 'CookieAdmin', $key);
				}
			}
		}

		return $strings;
	}
	
	static function string($string, $key){
		if(cookieadmin_is_polylang_active()){
			return pll__($string);
		}else if(cookieadmin_is_wpml_active()){
			return apply_filters( 'wpml_translate_single_string', $strings[$key], 'CookieAdmin', $key);
		}
	}
}