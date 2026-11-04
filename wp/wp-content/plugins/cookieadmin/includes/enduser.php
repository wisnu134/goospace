<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Enduser{
	
	static $http_cookies = array();
	static $categorized_cookies = array();
	
	static function enqueue_scripts(){
		global $wpdb;

		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');
		$policy = cookieadmin_load_policy();
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');
		//cookieadmin_r_print($view);
		//cookieadmin_r_print($policy);
		
		if(!empty($policy) && !empty($view) && !cookieadmin_is_editor_mode()){
			
			add_filter('mepr_design_style_handle_prefixes', '\CookieAdmin\Enduser::memberpress_allow_style_prefix');
		
			wp_enqueue_style('cookieadmin-style', COOKIEADMIN_PLUGIN_URL . 'assets/css/consent.css', [], COOKIEADMIN_VERSION);
			
			$js_deps = [];
			// Free consent.js is the base script from where the functionality gets triggered
			// So we need to make sure the dependencies of free script gets loaded first
			// Like the pro/consent.js is a dependency of the free one.
			if(defined('COOKIEADMIN_PREMIUM')){
				$js_deps[] = 'cookieadmin_pro_js';
			}
			
			wp_enqueue_script('cookieadmin_js', COOKIEADMIN_PLUGIN_URL . 'assets/js/consent.js', $js_deps, COOKIEADMIN_VERSION);
		
			$policy[$view]['ajax_url'] = admin_url('admin-ajax.php');
			$policy[$view]['nonce'] = wp_create_nonce('cookieadmin_js_nonce');
			$policy[$view]['http_cookies'] = self::$http_cookies;
			$policy[$view]['home_url'] = home_url();
			$policy[$view]['plugin_url'] = COOKIEADMIN_URL;
			$policy[$view]['is_pro'] = (defined('COOKIEADMIN_PREMIUM') ? COOKIEADMIN_PREMIUM : 0);
			$policy[$view]['ssl'] = is_ssl();
			
			$base_path = parse_url(home_url(), PHP_URL_PATH) ?: '/';
			$base_path = ($base_path !== '/') ? rtrim($base_path, '/') . '/' : '/';
			
			// Used for setting cookie
			$policy[$view]['base_path'] = $base_path;
			
			// NOTE: Check the polylang string registration if changing these
			$policy[$view]['lang']['show_less'] = __('Show less', 'cookieadmin');
			$policy[$view]['lang']['duration'] = __('Duration', 'cookieadmin');
			$policy[$view]['lang']['session'] = __('Session', 'cookieadmin');
			$policy[$view]['lang']['days'] = __('Days', 'cookieadmin');
			
			// cookieadmin_r_print($policy);die();
			
			$rows = $wpdb->get_results("SELECT cookie_name, category, expires, description, patterns FROM {$table_name}");
			$cookie_data = array();

			foreach ($rows as $row) {
				$cookie_data[$row->cookie_name] = $row;
			}
			
			$policy[$view]['categorized_cookies'] = self::$categorized_cookies = $cookie_data;
			
			$policy[$view] = apply_filters('cookieadmin_before_localize', $policy[$view]);
			
			wp_localize_script('cookieadmin_js', 'cookieadmin_policy', $policy[$view]);
			
		}
	}

	static function cookieadmin_block_cookie_init_php(){
		
		if(headers_sent() || (is_admin() && !wp_doing_ajax()) || defined('COOKIEADMIN_SCANNER') || cookieadmin_is_editor_mode()){
			return;
		}

		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');

		if(empty($view)){
			return;
		}

		$http_cookies = array();
		$set_cookie_headers = array();

		foreach(headers_list() as $header) {
			if(stripos(trim($header), 'Set-Cookie:') === 0){
				$header = trim(substr($header, strlen('Set-Cookie:')));
				$set_cookie_headers[] = $header;
			}
		}

		if(empty($set_cookie_headers)){
			return;
		}

		$policy = cookieadmin_load_policy();

		if(empty($policy) || empty($policy[$view])){
			return;
		}

		$categories = self::get_cookie_categories();
		$consent = self::get_consent();
		$preload = self::get_preload_categories($policy, $view);
		$allowed_headers = array();

		foreach($set_cookie_headers as $set_cookie){
			$name = self::get_cookie_name_from_header($set_cookie);

			if(empty($name)){
				continue;
			}

			if(self::is_response_cookie_allowed($set_cookie, $categories, $consent, $preload)){
				$allowed_headers[] = $set_cookie;
				continue;
			}

			$http_cookies[$name]['string'] = trim($set_cookie);
		}

		header_remove('Set-Cookie');

		foreach($allowed_headers as $set_cookie){
			header('Set-Cookie: ' . $set_cookie, false);
		}

		$http_cookies['cookieadmin_consent'] = ["string" => "cookieadmin_consent=CookieAdmin Cookie Initialization"];
		
		self::$http_cookies = $http_cookies;
	}

	static function get_cookie_categories(){
		global $wpdb;

		$table_name = $wpdb->prefix . 'cookieadmin_cookies';
		if(!self::cookieadmin_table_exists($table_name)){
			return array();
		}

		$categories = array(
			'exact' => array(),
			'prefix' => array(),
		);
		$rows = $wpdb->get_results("SELECT cookie_name, raw_name, category FROM {$table_name}");

		foreach($rows as $row){
			if(!empty($row->cookie_name) && !empty($row->category)){
				$category = strtolower($row->category);
				$categories['exact'][$row->cookie_name] = $category;

				if(!empty($row->raw_name)){
					$categories['exact'][$row->raw_name] = $category;

					if($row->raw_name !== $row->cookie_name && strpos($row->raw_name, $row->cookie_name) === 0){
						$categories['prefix'][$row->cookie_name] = $category;
					}
				}
			}
		}

		uksort($categories['prefix'], function($a, $b){
			return strlen($b) - strlen($a);
		});

		return $categories;
	}

	static function get_consent(){
		if(empty($_COOKIE['cookieadmin_consent'])){
			return array();
		}

		$consent = json_decode(wp_unslash($_COOKIE['cookieadmin_consent']), true);
		if(!is_array($consent)){
			return array();
		}

		$sanitized = array();
		foreach($consent as $key => $value){
			$sanitized[sanitize_key($key)] = sanitize_text_field($value);
		}

		return $sanitized;
	}

	static function get_preload_categories($policy, $view){
		if(empty($policy) || empty($policy[$view]['preload']) || !is_array($policy[$view]['preload'])){
			return array();
		}

		$preload = array();
		foreach($policy[$view]['preload'] as $category){
			$preload[] = strtolower(sanitize_key($category));
		}

		return $preload;
	}

	static function get_cookie_name_from_header($set_cookie){
		$parts = explode('=', $set_cookie, 2);
		$name = trim($parts[0]);

		if(empty($name)){
			return '';
		}

		return $name;
	}

	static function is_response_cookie_allowed($set_cookie, $categories, $consent, $preload = array()){
		$name = self::get_cookie_name_from_header($set_cookie);

		if(empty($name)){
			return false;
		}

		// A deletion never adds a cookie and must not be prevented.
		if(preg_match('/(?:^|;)\s*max-age\s*=\s*(-?\d+)/i', $set_cookie, $age) && (int) $age[1] <= 0){
			return true;
		}

		if(preg_match('/(?:^|;)\s*expires\s*=\s*([^;]+)/i', $set_cookie, $expires)){
			$expires_at = strtotime($expires[1]);
			if($expires_at !== false && $expires_at < time()){
				return true;
			}
		}

		if($name === 'cookieadmin_consent'){
			return true;
		}

		$category = self::get_cookie_category($name, $categories);

		if(empty($category)){
			return !empty($consent['accept']) && $consent['accept'] === 'true';
		}

		if($category === 'necessary' || in_array($category, $preload, true)){
			return true;
		}

		if(!empty($consent['reject']) && $consent['reject'] === 'true'){
			return false;
		}

		if(!empty($consent['accept']) && $consent['accept'] === 'true'){
			return true;
		}

		return !empty($consent[$category]) && $consent[$category] === 'true';
	}

	static function get_cookie_category($name, $categories){
		if(!empty($categories['exact'][$name])){
			return $categories['exact'][$name];
		}

		if(empty($categories['prefix'])){
			return '';
		}

		foreach($categories['prefix'] as $prefix => $category){
			if(strpos($name, $prefix) === 0){
				return $category;
			}
		}

		return '';
	}
	
	static function block_scripts(){

		if(wp_doing_ajax() || is_admin() || defined('REST_REQUEST') || defined('COOKIEADMIN_SCANNER') || cookieadmin_is_editor_mode()){
			return;
		}
		
		$settings = get_option('cookieadmin_settings');

		if(empty($settings) || empty($settings['block_scripts'])){
				return;
		}

		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');
		$policy = cookieadmin_load_policy();
		if(empty($policy) || empty($view)){
			return;
		}

		ob_start([__CLASS__, 'update_tracking_scripts']);
	}

	static function update_tracking_scripts($html){
		// Never let a blocking failure blank the page, serve it unmodified instead
		try{
			return self::block_tracking_scripts($html);
		}catch(\Throwable $e){
			return $html;
		}
	}

	private static function block_tracking_scripts($html){
		global $cookieadmin_settings;

		if(!is_string($html) || stripos($html, '<script') === false){
			return $html;
		}

		if(empty(self::$categorized_cookies)){
			return $html;
		}

		$cookieadmin_consent = self::get_consent();

		$len = strlen($html);
		$cursor = 0;
		$output = '';

		while ($cursor < $len) {
			// Find next <script tag
			$script_pos = stripos($html, '<script', $cursor);
			if ($script_pos === false) {
				$output .= substr($html, $cursor);
				break;
			}

			// Append everything prior to this <script tag
			$output .= substr($html, $cursor, $script_pos - $cursor);

			// Check word boundary after '<script' (must be followed by whitespace, '>', or '/')
			$after_script = $script_pos + 7; // strlen('<script')
			if ($after_script < $len) {
				$next_char = $html[$after_script];
				if ($next_char !== ' ' && $next_char !== "\t" && $next_char !== "\n" && $next_char !== "\r" && $next_char !== '>' && $next_char !== '/') {
					// e.g. <scripture> or <scripts>
					$output .= substr($html, $script_pos, 7);
					$cursor = $after_script;
					continue;
				}
			}

			// Find end of opening tag '>' while respecting quotes
			$open_tag_end = false;
			$in_quote = false;
			$quote_char = '';

			for ($i = $after_script; $i < $len; $i++) {
				$char = $html[$i];
				if ($in_quote) {
					if ($char === $quote_char) {
						$in_quote = false;
					}
				} else {
					if ($char === '"' || $char === "'") {
						$in_quote = true;
						$quote_char = $char;
					} elseif ($char === '>') {
						$open_tag_end = $i;
						break;
					}
				}
			}

			if ($open_tag_end === false) {
				// Unclosed opening tag; append remainder and exit
				$output .= substr($html, $script_pos);
				break;
			}

			$attrs = substr($html, $after_script, $open_tag_end - $after_script);

			// Find closing </script>
			$close_tag_pos = stripos($html, '</script>', $open_tag_end + 1);
			if ($close_tag_pos === false) {
				// Unclosed script tag; append remainder and exit
				$output .= substr($html, $script_pos);
				break;
			}

			$content = substr($html, $open_tag_end + 1, $close_tag_pos - ($open_tag_end + 1));
			$close_tag_end = $close_tag_pos + 9; // strlen('</script>')
			$full_tag = substr($html, $script_pos, $close_tag_end - $script_pos);

			$output .= self::process_script_tag($attrs, $content, $full_tag, $cookieadmin_consent, $cookieadmin_settings);
			$cursor = $close_tag_end;
		}

		return $output;
	}

	private static function process_script_tag($attrs, $content, $full_tag, $cookieadmin_consent, $cookieadmin_settings){
		if(preg_match('/(?<![\w-])type\s*=\s*["\']text\/plain["\']/i', $attrs)){
			return $full_tag;
		}

		if(preg_match('/(?<![\w-])(id|src)\s*=\s*["\'][^"\']*cookieadmin[^"\']*["\']/i', $attrs)){
			return $full_tag;
		}

		if(preg_match('/(?<![\w-])type\s*=\s*["\']([^"\']+)["\']/i', $attrs, $type_match)){
			$type = strtolower(trim($type_match[1]));
			if($type !== 'text/javascript' && $type !== 'module'){
				return $full_tag;
			}
		}

		$src = '';
		if(preg_match('/(?<![\w-])src\s*=\s*["\']([^"\']*)["\']/i', $attrs, $src_match)){
			$src = $src_match[1];
		}

		$match_against = !empty($src) ? $src : trim($attrs . ' ' . $content);

		if(empty($match_against)){
			return $full_tag;
		}
		
		if(!empty($cookieadmin_settings['cookieadmin_google_advance_consent_mode'])){
			// External Google tag loader
			if(!empty($src) && stripos($src, 'googletagmanager.com/gtag/js') !== false){
				return $full_tag;
			}
			// Inline gtag() snippet
			if(empty($src) && stripos($content, 'gtag(') !== false){
				return $full_tag;
			}
		}

		foreach (self::$categorized_cookies as $item) {
			$category = !empty($item->category) ? strtolower($item->category) : '';
			$patterns = !empty($item->patterns) ? json_decode($item->patterns, true) : '';

			if(empty($patterns) || empty($category)){
				continue;
			}

			foreach ($patterns as $pattern) {
				if(strpos($match_against, $pattern) !== false){
					if($category !== 'necessary' && 
						(empty($cookieadmin_consent) || 
							(!empty($cookieadmin_consent[$category]) && $cookieadmin_consent[$category] == 'false') || 
							(!empty($cookieadmin_consent['reject']) && $cookieadmin_consent['reject'] == 'true')
						)
					){
						// Remove any existing type attribute to prevent duplicate type="..." attributes
						$sanitized_attrs = preg_replace('/(?<![\w-])type\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
						$sanitized_attrs = trim($sanitized_attrs);
						$attr_str = !empty($sanitized_attrs) ? ' ' . $sanitized_attrs : '';

						return '<script type="text/plain" data-cookieadmin-category="' . esc_attr($category) . '"' . $attr_str . '>' . $content . '</script>';
					}
				}
			}
		}

		return $full_tag;
	}
	
	static function cookieadmin_show_banner(){

		$view = get_option('cookieadmin_law', 'cookieadmin_gdpr');
		$policy = cookieadmin_load_policy();

		// Do not show banner if banner is off via Geo rule
		if(empty($view)){
			return;
		}
		
		$raw_template = cookieadmin_load_consent_template($policy[$view], $view);
		
		if(!is_array($raw_template) || empty($raw_template)){
			return false;
		}

		$templates = implode('', $raw_template);
		
		$allowed_tags = cookieadmin_kses_allowed_html();
		
		$templates = apply_filters('cookieadmin_after_banner', $templates);
		
		// var_dump($policy[$view]);
		echo wp_kses($templates, $allowed_tags);
	}
	
	static function cookieadmin_table_exists($table_name) {
		global $wpdb;
		
		$query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
		
		return $wpdb->get_var($query) === $table_name;
	}
	
	static function memberpress_allow_style_prefix($prefix_arr){
		$prefix_arr[] = 'cookieadmin';
		return $prefix_arr;
	}
}
