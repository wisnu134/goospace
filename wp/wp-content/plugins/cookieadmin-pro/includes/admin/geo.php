<?php

namespace CookieAdminPro\Admin;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Geo{

	static function geo_admin_page(){

		\CookieAdmin\Admin::header_theme(__('Geo Targeting', 'cookieadmin'));

		$geo_enabled = get_option('cookieadmin_geo_enabled', false);
		$db_data = get_option('cookieadmin_pro_geo_target_db_download', []);
		if(!empty($db_data['filename']) && !empty($db_data['time'])){
			$wp_upload_dir_info = wp_upload_dir();
			$upload_dir = $wp_upload_dir_info['basedir'] . '/cookieadmin/';
			$db_data['db_exists'] = file_exists($upload_dir . $db_data['filename']);
		}
		$rules = get_option('cookieadmin_geo_rules', []);
		$countries = include(COOKIEADMIN_PRO_DIR . 'lib/locations.php');
		$laws = apply_filters('cookieadmin_geo_law_options', [
			'cookieadmin_gdpr' => __('GDPR', 'cookieadmin'),
			'cookieadmin_us' => __('US State Laws', 'cookieadmin'),
		]);

		echo '<div class="cookieadmin-geo-page">';

		echo '<div id="cookieadmin_geo_notice" class="cookieadmin-geo-notice"></div>';

		// Enable/Disable Toggle
		echo '<div class="cookieadmin-geo-enable-section-con"><div class="cookieadmin-geo-enable-section">
			<div>
				<p class="cookieadmin-geo-enable-label">' . esc_html__('Enable Geo Targeting', 'cookieadmin') . '</p>
				<p class="cookieadmin-geo-enable-desc">' . esc_html__('When enabled, visitors will see the consent banner based on their geographic location.', 'cookieadmin') . '</p>
				<p class="cookieadmin-geo-enable-desc">' . esc_html__('When disabled, visitors will see the default consent banner.', 'cookieadmin') . '</p>
			</div>
			<label class="cookieadmin-toggle-wrap">
				<input type="checkbox" id="cookieadmin_geo_enabled_toggle" ' . checked($geo_enabled, true, false) . '>
				<span class="cookieadmin-toggle-track"><span class="cookieadmin-toggle-thumb"></span></span>
			</label>
		</div>';

		// Enable/Disable Toggle
		echo '<div class="cookieadmin-geo-enable-section">
			<div>
				<p class="cookieadmin-geo-enable-label">' . esc_html__('Download Country Database', 'cookieadmin') . '</p>
				<p class="cookieadmin-geo-enable-desc">' . esc_html__('Country database file helps us to detect the end user\'s country accurately.', 'cookieadmin') . '</p>
				<p>
					<span class="cookieadmin-geo-db-avl" '.(empty($db_data['db_exists']) ? 'style="display:none;"' : '').'>'.esc_html__('Last Database Updated At : ', 'cookieadmin').'
						<span class="cookieadmin-geo-db-download-time">'.(!empty($db_data['time']) ? wp_date('Y-m-d h:i:s', $db_data['time']) : '').'</span>
					</span>
					<span class="cookieadmin-geo-no-db" '.(!empty($db_data['db_exists']) ? 'style="display:none;"' : '').'>'.esc_html__('Database does not exists. download now!', 'cookieadmin').'</span>
				</p>
			</div>
			<div class="cookieadmin-geo-db-download">
				<input type="button" class="cookieadmin-btn cookieadmin-btn-primary" value="'.((!empty($db_data['filename']) && !empty($db_data['db_exists'])) ? esc_html__('Update Database', 'cookieadmin') : esc_html__('Download Database', 'cookieadmin')).'" id="cookieadmin-geo-download-db-btn">
			</div>
		</div></div>';

		// Presets Section
		echo '<div class="cookieadmin-geo-presets">
			<p class="cookieadmin-geo-presets-title">' . esc_html__('Add GEO Targetting Rules', 'cookieadmin') . '</p>
			<div class="cookieadmin-geo-presets-grid">
				<button type="button" class="cookieadmin-geo-preset-btn cookieadmin-geo-preset-gdpr" data-preset="gdpr">
					<span class="cookieadmin-geo-preset-icon"><span class="dashicons dashicons-privacy"></span></span>
					<span class="cookieadmin-geo-preset-label">' . esc_html__('GDPR Rule', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-desc">' . esc_html__('Europe + EEA countries', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-add"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Rule', 'cookieadmin') . '</span>
				</button>
				<button type="button" class="cookieadmin-geo-preset-btn cookieadmin-geo-preset-us" data-preset="us">
					<span class="cookieadmin-geo-preset-icon"><span class="dashicons dashicons-flag"></span></span>
					<span class="cookieadmin-geo-preset-label">' . esc_html__('US State Laws', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-desc">' . esc_html__('United States', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-add"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Rule', 'cookieadmin') . '</span>
				</button>
				<button type="button" class="cookieadmin-geo-preset-btn cookieadmin-geo-preset-dpdp" data-preset="dpdp">
					<span class="cookieadmin-geo-preset-icon"><span class="dashicons dashicons-shield-alt"></span></span>
					<span class="cookieadmin-geo-preset-label">' . esc_html__('DPDP Act (India)', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-desc">' . esc_html__('India', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-add"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Rule', 'cookieadmin') . '</span>
				</button>
				<button type="button" class="cookieadmin-geo-preset-btn cookieadmin-geo-preset-us" data-preset="custom">
					<span class="cookieadmin-geo-preset-icon"><span class="dashicons dashicons-admin-settings"></span></span>
					<span class="cookieadmin-geo-preset-label">' . esc_html__('Add Custom Rule', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-desc">' . esc_html__('Choose countries', 'cookieadmin') . '</span>
					<span class="cookieadmin-geo-preset-add"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__('Add Rule', 'cookieadmin') . '</span>
				</button>
			</div>
		</div>';

		// Modal Form (hidden by default)
		echo '<div id="cookieadmin_geo_modal" class="cookieadmin_modal-overlay">';
		echo '<div class="cookieadmin_modal-container cookieadmin-geo-modal">';
		self::render_card_form($countries, $laws);
		echo '</div>';
		echo '</div>';

		// Rules Table
		echo '<div id="cookieadmin_geo_table_wrap" style="margin-bottom:16px;">';
		self::render_rules_table($rules, $countries, $laws);
		echo '</div>';

		// Empty State
		$empty_display = empty($rules) ? 'block' : 'none';
		echo '<div id="cookieadmin_geo_empty_state" class="cookieadmin-geo-empty" style="display:' . esc_attr($empty_display) . ';">
			<div class="cookieadmin-geo-empty-icon"><span class="dashicons dashicons-location-alt"></span></div>
			<p>' . esc_html__('No geo targeting rules configured yet.', 'cookieadmin') . '</p>
			<p>' . esc_html__('Use the presets above or add a custom rule.', 'cookieadmin') . '</p>
		</div>';

		echo '</div>'; // .cookieadmin-geo-page

		\CookieAdmin\Admin::footer_theme();
	}

	static function render_card_form($countries, $laws){
		$law_options = '';
		foreach($laws as $key => $label){
			$law_options .= '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
		}

		$country_options = '';
		foreach($countries['countries'] as $code => $name){
			$country_options .= '<option value="' . esc_attr($code) . '">' . esc_html($code . ' - ' . $name) . '</option>';
		}

		echo '<div class="cookieadmin_modal-header">
				<span class="cookieadmin_modal-title cookieadmin-geo-modal-title">' . esc_html__('Rule Configuration', 'cookieadmin') . '</span>
				<button type="button" class="cookieadmin_dialog_modal_close_btn" id="cookieadmin_geo_modal_close">&times;</button>
				<input type="hidden" id="cookieadmin_geo_card_id" value="">
			</div>
			<div class="cookieadmin_modal-body">
				
				<!-- Rule Name -->
				<div class="cookieadmin_form-group">
					<label for="cookieadmin_geo_card_name">' . esc_html__('Rule Name', 'cookieadmin') . '</label>
					<input type="text" id="cookieadmin_geo_card_name" value="" placeholder="' . esc_attr__('e.g. GDPR Countries', 'cookieadmin') . '" style="width:100%;">
				</div>
				
				<!-- Law Type -->
				<div class="cookieadmin_form-group">
					<label for="cookieadmin_geo_card_law">' . esc_html__('Law Type', 'cookieadmin') . '</label>
					<select id="cookieadmin_geo_card_law" style="width:100%;">
						<option disabled>' . esc_html__('Select Law Type', 'cookieadmin') . '</option>
						<option value="">'.esc_html__('None', 'cookieadmin').'</option>
						' . $law_options . '
					</select>
				</div>
				
				<!-- Countries -->
				<div class="cookieadmin_form-group">
					<label>' . esc_html__('Countries', 'cookieadmin') . '</label>
					<div id="cookieadmin_geo_card_tags" class="cookieadmin-geo-country-tags"></div>
					<select id="cookieadmin_geo_card_country_select" style="width:100%; margin-top:8px;">
						<option value="">' . esc_html__('+ Add Country', 'cookieadmin') . '</option>
						' . $country_options . '
					</select>
					<div class="cookieadmin-geo-shortcuts">
						<span class="cookieadmin-geo-shortcuts-label">' . esc_html__('Shortcuts:', 'cookieadmin') . '</span>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="eu">' . esc_html__('EU', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="eea">' . esc_html__('EEA', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="africa">' . esc_html__('Africa', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="asia">' . esc_html__('Asia', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="europe">' . esc_html__('Europe', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="north_america">' . esc_html__('North America', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="south_america">' . esc_html__('South America', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="oceania">' . esc_html__('Oceania', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="caribbean">' . esc_html__('Caribbean', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn" data-continent="asean">' . esc_html__('ASEAN', 'cookieadmin') . '</button>
						<button type="button" class="cookieadmin-geo-shortcut-btn cookieadmin-geo-shortcut-worldwide" data-continent="worldwide">' . esc_html__('Worldwide', 'cookieadmin') . '</button>
					</div>
				</div>
				
				<!-- Feature Overrides -->
				<div class="cookieadmin_form-group">
					<label>' . esc_html__('Feature Overrides', 'cookieadmin') . '
						<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="' . esc_attr__('Inherit uses the global setting. On/Off overrides for this rule.', 'cookieadmin') . '"></span>
					</label>
					<div class="cookieadmin-grid-2" style="gap:12px;">
						<div class="cookieadmin-vertical">
							<span class="cookieadmin-text-muted" style="font-size:12px; font-weight:500;">' . esc_html__('Block Scripts', 'cookieadmin') . '</span>
							' . self::render_toggle_html('card', 'block_scripts', 'inherit') . '
						</div>
						
						<div class="cookieadmin-vertical">
							<span class="cookieadmin-text-muted" style="font-size:12px; font-weight:500;">' . esc_html__('Content Blocking', 'cookieadmin') . '</span>
							' . self::render_toggle_html('card', 'content_blocking', 'inherit') . '
						</div>
						
						<div class="cookieadmin-vertical">
							<span class="cookieadmin-text-muted" style="font-size:12px; font-weight:500;">' . esc_html__('Google Consent Mode', 'cookieadmin') . '</span>
							' . self::render_toggle_html('card', 'google_consent_mode_v2', 'inherit') . '
						</div>
						
						<div class="cookieadmin-vertical">
							<span class="cookieadmin-text-muted" style="font-size:12px; font-weight:500;">' . esc_html__('Clarity Consent', 'cookieadmin') . '</span>
							' . self::render_toggle_html('card', 'clarity_consent', 'inherit') . '
						</div>
					</div>
				</div>
			</div>
			<div class="cookieadmin_modal-footer">
				<button type="button" id="cookieadmin_geo_card_cancel" class="cookieadmin-btn cookieadmin-btn-secondary">' . esc_html__('Cancel', 'cookieadmin') . '</button>
				<button type="button" id="cookieadmin_geo_card_save" class="cookieadmin-btn cookieadmin-btn-primary">' . esc_html__('Save Rule', 'cookieadmin') . '</button><span class="spinner" style="display:none;"></span>
			</div>';
	}

	static function render_rules_table($rules, $countries, $laws){
		if(empty($rules)){
			return;
		}

		$shortcuts = $countries['continents'];
		$shortcut_labels = [
			'eu' => __('EU', 'cookieadmin'),
			'eea' => __('EEA', 'cookieadmin'),
			'africa' => __('Africa', 'cookieadmin'),
			'asia' => __('Asia', 'cookieadmin'),
			'europe' => __('Europe', 'cookieadmin'),
			'north_america' => __('North America', 'cookieadmin'),
			'south_america' => __('South America', 'cookieadmin'),
			'oceania' => __('Oceania', 'cookieadmin'),
			'antarctica' => __('Antarctica', 'cookieadmin'),
			'caribbean' => __('Caribbean', 'cookieadmin'),
			'asean' => __('ASEAN', 'cookieadmin'),
			'worldwide' => __('Worldwide', 'cookieadmin'),
		];

		echo '<div class="cookieadmin-card">
			<div class="cookieadmin-card-header">
				<span class="cookieadmin-card-title">
					<span class="dashicons dashicons-admin-site"></span>
					' . esc_html__('Geo Targeting Rules', 'cookieadmin') . '
				</span>
				<div style="display:flex; justify-content:center; gap:5px; align-items:center;">
				<span class="cookieadmin-badge cookieadmin-info">' . esc_html(count($rules)) . ' ' . esc_html(_n('rule', 'rules', count($rules), 'cookieadmin')) . '</span>
				<span class="spinner" style="margin:0; display:none;"></span>
				</div>
			</div>
			<div class="cookieadmin-card-body" style="padding:0;">
				<table class="cookieadmin-table">
					<thead>
						<tr>
							<th style="width:20%;">' . esc_html__('Rule Name', 'cookieadmin') . '</th>
							<th style="width:10%;">' . esc_html__('Law', 'cookieadmin') . '</th>
							<th style="width:25%;">' . esc_html__('Countries', 'cookieadmin') . '</th>
							<th style="width:7%;">' . esc_html__('Scripts', 'cookieadmin') . '</th>
							<th style="width:7%;">' . esc_html__('Content', 'cookieadmin') . '</th>
							<th style="width:7%;">' . esc_html__('Google', 'cookieadmin') . '</th>
							<th style="width:7%;">' . esc_html__('Clarity', 'cookieadmin') . '</th>
							<th style="width:7%;">' . esc_html__('Actions', 'cookieadmin') . '</th>
						</tr>
					</thead>
					<tbody>';

		foreach($rules as $index => $rule){
			$rule_id = $index;
			$rule_name = !empty($rule['name']) ? $rule['name'] : '';
			$rule_law = !empty($rule['law']) ? $rule['law'] : '';
			$rule_law_label = isset($laws[$rule_law]) ? $laws[$rule_law] : $rule_law;
			$rule_countries = !empty($rule['countries']) && is_array($rule['countries']) ? $rule['countries'] : [];
			
			$country_badges = '';
			$display_count = 0;
			$total_items = count($rule_countries);
			
			for($i = 0; $i < $total_items && $display_count < 4; $i++){
				$code = $rule_countries[$i];
				if($code === 'worldwide'){
					$label = isset($shortcut_labels[$code]) ? $shortcut_labels[$code] : $code;
					$country_badges .= '<span class="cookieadmin-badge cookieadmin-primary" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' . esc_html($label) . '</span>';
				}elseif(isset($shortcuts[$code])){
					$label = isset($shortcut_labels[$code]) ? $shortcut_labels[$code] : $code;
					$count = count($shortcuts[$code]);
					$country_badges .= '<span class="cookieadmin-badge cookieadmin-primary" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' . esc_html($label) . ' (' . esc_html($count) . ')</span>';
				}else{
					$name = isset($countries['countries'][$code]) ? $countries['countries'][$code] : $code;
					$country_badges .= '<span class="cookieadmin-badge cookieadmin-info" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' . esc_html($code) . '</span>';
				}
				$display_count++;
			}
			
			if($total_items > $display_count){
				$more = $total_items - $display_count;
				$country_badges .= '<span class="cookieadmin-text-muted" style="font-size:11px;">+' . esc_html($more) . '</span>';
			}

			$block_scripts = !empty($rule['block_scripts']) ? $rule['block_scripts'] : 'inherit';
			$content_blocking = !empty($rule['content_blocking']) ? $rule['content_blocking'] : 'inherit';
			$google_consent_mode_v2 = !empty($rule['google_consent_mode_v2']) ? $rule['google_consent_mode_v2'] : 'inherit';
			$clarity_consent = !empty($rule['clarity_consent']) ? $rule['clarity_consent'] : 'inherit';
			
			// If law type is empty that means it is set to none, so we turn everything off on display
			if(empty($rule_law_label)){
				$block_scripts  = 'off';
				$content_blocking  = 'off';
				$google_consent_mode_v2 = 'off';
				$clarity_consent = 'off';
			}

			echo '<tr data-rule-id="' . esc_attr($rule_id) . '">
				<td><strong>' . esc_html($rule_name) . '</strong></td>
				<td>' . (!empty($rule_law_label) ? esc_html($rule_law_label) : __('None', 'cookieadmin')). '</td>
				<td>' . $country_badges . '</td>
				<td>' . self::render_badge($block_scripts) . '</td>
				<td>' . self::render_badge($content_blocking) . '</td>
				<td>' . self::render_badge($google_consent_mode_v2) . '</td>
				<td>' . self::render_badge($clarity_consent) . '</td>
				<td style="text-align:right;">
					<button type="button" class="cookieadmin-btn cookieadmin-btn-ghost cookieadmin-btn-sm cookieadmin-geo-table-edit" data-rule-id="' . esc_attr($rule_id) . '" title="' . esc_attr__('Edit', 'cookieadmin') . '" style="margin-right:2px;">
						<span class="dashicons dashicons-edit"></span>
					</button>
					<button type="button" class="cookieadmin-btn cookieadmin-btn-ghost cookieadmin-btn-sm cookieadmin-geo-table-delete" data-rule-id="' . esc_attr($rule_id) . '" title="' . esc_attr__('Delete', 'cookieadmin') . '">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</td>
			</tr>';
		}

		echo '</tbody>
				</table>
			</div>
		</div>';
	}

	static function render_toggle_html($suffix, $field, $value){
		$inherit_active = ($value === 'inherit') ? ' cookieadmin-geo-toggle-active' : '';
		$on_active = ($value === 'on') ? ' cookieadmin-geo-toggle-active' : '';
		$off_active = ($value === 'off') ? ' cookieadmin-geo-toggle-active' : '';

		return '<div class="cookieadmin-geo-toggle" id="cookieadmin_geo_card_' . esc_attr($field) . '" data-field="' . esc_attr($field) . '">
			<input type="hidden" class="cookieadmin-geo-toggle-value" value="' . esc_attr($value) . '">
			<button type="button" class="cookieadmin-geo-toggle-btn cookieadmin-geo-toggle-inherit' . $inherit_active . '" data-value="inherit">' . esc_html__('Inherit', 'cookieadmin') . '</button>
			<button type="button" class="cookieadmin-geo-toggle-btn cookieadmin-geo-toggle-on' . $on_active . '" data-value="on">' . esc_html__('On', 'cookieadmin') . '</button>
			<button type="button" class="cookieadmin-geo-toggle-btn cookieadmin-geo-toggle-off' . $off_active . '" data-value="off">' . esc_html__('Off', 'cookieadmin') . '</button>
		</div>';
	}

	static function render_badge($value){
		$class = '';
		$label = '';
		switch($value){
			case 'inherit':
				$class = 'cookieadmin-info';
				$label = __('Inherit', 'cookieadmin');
				break;
			case 'on':
				$class = 'cookieadmin-success';
				$label = __('On', 'cookieadmin');
				break;
			case 'off':
				$class = 'cookieadmin-danger';
				$label = __('Off', 'cookieadmin');
				break;
			default:
				$class = 'cookieadmin-info';
				$label = __('Inherit', 'cookieadmin');
		}
		return '<span class="cookieadmin-badge ' . esc_attr($class) . '" data-value="' . esc_attr($value) . '" title="' . esc_attr($label) . '" style="padding:1px 8px; font-size:11px;">' . esc_html($label) . '</span>';
	}
	
	// Deleting the rule based on ID
	static function delete_rule($id){	
		$rules = get_option('cookieadmin_geo_rules', []);
		
		if(empty($rules)){
			wp_send_json_error(['message' => __('There is no rule added to delete from', 'cookieadmin')]);
		}
		
		if(empty($rules[$id])){
			wp_send_json_error(['message' => __('Request rule ID is not present, can not delete', 'cookieadmin')]);
		}
		
		unset($rules[$id]);

		update_option('cookieadmin_geo_rules', $rules);

		wp_send_json_success(['message' => __('Rule Deleted successfully', 'cookieadmin'), 'rules' => $rules]);
		
	}

	static function save_geo_settings(){
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');

		if(!current_user_can('administrator')){
			wp_send_json_error(['message' => __('Sorry, but you do not have permissions to perform this action', 'cookieadmin')]);
		}
		
		if(empty($_REQUEST['rule'])){
			// If we need to delete the rule?
			if(!empty($_REQUEST['delete_id'])){
				$delete_id = sanitize_key(wp_unslash($_REQUEST['delete_id']));
				self::delete_rule($delete_id);
			}
			
			wp_send_json_error(['message' => __('Request came empty', 'cookieadmin')]);
		}

		$rule_json = !empty($_REQUEST['rule']) ? wp_unslash($_REQUEST['rule']) : '';		
		
		$rule = json_decode($rule_json, true);

		if(empty($rule) || !is_array($rule)){
			wp_send_json_error(['message' => __('Invalid rules data', 'cookieadmin')]);
		}

		$rule = map_deep($rule, 'sanitize_text_field');

		$sanitized_rules = [];
		$locations = include(COOKIEADMIN_PRO_DIR . 'lib/locations.php');
		$valid_countries = $locations['countries'];
		$valid_shortcuts = $locations['continents'];
		$valid_laws = apply_filters('cookieadmin_geo_law_options', [
			'cookieadmin_gdpr' => 'GDPR',
			'cookieadmin_us' => 'US State Laws',
		]);

		if(empty($rule['name'])){
			wp_send_json_error(['message' => __('Enter a valid rule name', 'cookieadmin')]);
		}

		if(!empty($rule['law']) && !isset($valid_laws[$rule['law']])){
			wp_send_json_error(['message' => __('Please select a valid law type', 'cookieadmin')]);
		}

		$countries = [];
		if(!empty($rule['countries']) && is_array($rule['countries'])){
			foreach($rule['countries'] as $country_code){
				if(isset($valid_countries[$country_code])){
					$countries[] = $country_code;
				}elseif(isset($valid_shortcuts[$country_code])){
					$countries[] = $country_code;
				}elseif($country_code === 'worldwide'){
					$countries[] = $country_code;
				}
			}
		}

		if(empty($countries)){
			wp_send_json_error(['message' => __('Please select a valid country', 'cookieadmin')]);
		}
		
		$sanitized_rules = get_option('cookieadmin_geo_rules', []);
		
		if(!empty($rule['id'])){
			$rule_id = $rule['id'];
		} else {
			$rule_id = 'rule_' . uniqid();
		}

		$sanitized_rules[$rule_id] = [
			'name' => $rule['name'],
			'law' => !empty($rule['law']) ? $rule['law'] : '',
			'countries' => $countries,
			'block_scripts' => in_array($rule['block_scripts'], ['inherit', 'on', 'off']) ? $rule['block_scripts'] : 'inherit',
			'content_blocking' => in_array($rule['content_blocking'], ['inherit', 'on', 'off']) ? $rule['content_blocking'] : 'inherit',
			'google_consent_mode_v2' => in_array($rule['google_consent_mode_v2'], ['inherit', 'on', 'off']) ? $rule['google_consent_mode_v2'] : 'inherit',
			'clarity_consent' => in_array($rule['clarity_consent'], ['inherit', 'on', 'off']) ? $rule['clarity_consent'] : 'inherit',
		];

		update_option('cookieadmin_geo_rules', $sanitized_rules, true);

		wp_send_json_success([
			'message' => __('Geo targeting rules saved successfully', 'cookieadmin'),
			'rules' => $sanitized_rules
		]);
	}

	static function add_default_rules_ajax(){
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');

		if(!current_user_can('administrator')){
			wp_send_json_error(['message' => __('Sorry, but you do not have permissions to perform this action', 'cookieadmin')]);
		}

		$preset = !empty($_REQUEST['preset']) ? sanitize_text_field(wp_unslash($_REQUEST['preset'])) : '';
		$rules = get_option('cookieadmin_geo_rules', []);

		if($preset === 'gdpr'){
			$rules[] = [
				'id' => 'rule_' . uniqid(),
				'name' => __('GDPR Countries', 'cookieadmin'),
				'law' => 'cookieadmin_gdpr',
				'countries' => ['europe'],
				'block_scripts' => 'on',
				'content_blocking' => 'on',
				'google_consent_mode_v2' => 'on',
				'clarity_consent' => 'inherit',
			];
		}elseif($preset === 'us'){
			$rules[] = [
				'id' => 'rule_' . uniqid(),
				'name' => __('US State Laws', 'cookieadmin'),
				'law' => 'cookieadmin_us',
				'countries' => ['US'],
				'block_scripts' => 'inherit',
				'content_blocking' => 'inherit',
				'google_consent_mode_v2' => 'inherit',
				'clarity_consent' => 'inherit',
			];
		}

		update_option('cookieadmin_geo_rules', $rules, true);

		wp_send_json_success(['rules' => $rules]);
	}

	static function toggle_geo_enabled(){
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');

		if(!current_user_can('administrator')){
			wp_send_json_error(['message' => __('Sorry, but you do not have permissions to perform this action', 'cookieadmin')]);
		}

		$enabled = !empty($_REQUEST['enabled']) ? true : false;
		update_option('cookieadmin_geo_enabled', $enabled, true);

		wp_send_json_success(['message' => $enabled ? __('Geo targeting enabled', 'cookieadmin') : __('Geo targeting disabled', 'cookieadmin')]);
	}

	static function show_db_update_notice(){
		global $cookieadmin;
		// Check if the page belongs to the cookieadmin
		$current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		if(strpos($current_page, 'cookieadmin') !== 0){
			return false;
		}
		// Chekc if the database has been updated
		$db_data = get_option('cookieadmin_pro_geo_db_update_available', []);
		if(empty($db_data['filename'])){
			return false;
		}

		echo '<div class="notice notice-warning is-dismissible" id="cookieadmin_pro_remove_geo_db_notice">
			<p>
				'.cookieadmin_logo_svg().'</br>
				<strong>'.esc_html__('GeoIP database update.', 'cookieadmin').'</strong>
				'.esc_html__('A newer GeoIP database is available for download. Visit ', 'cookieadmin').'
				<a href="'.esc_url(admin_url('admin.php?page=cookieadmin-geo-targeting')).'">'.esc_html__('GEO Targeting', 'cookieadmin').'</a>'.esc_html__(' page to download the database.','cookieadmin').'
			</p>
		</div>';

		wp_register_script('cookieadmin-pro-remove-geo-db-notice', '', ['jquery'], '', true);
		wp_enqueue_script('cookieadmin-pro-remove-geo-db-notice');
		wp_add_inline_script('cookieadmin-pro-remove-geo-db-notice', 'jQuery("#cookieadmin_pro_remove_geo_db_notice").on("click",
		function(e){
			
			let target = jQuery(e.target);
			if(!target.hasClass("notice-dismiss")){
				return;
			}
			
			var data;

			// Hide it
			jQuery("#cookieadmin_pro_remove_geo_db_notice").remove();

			// Save this preference
			jQuery.post("'.admin_url('admin-ajax.php?action=cookieadmin_pro_ajax_handler&cookieadmin_act=close_geo_db_update_notice').'&cookieadmin_pro_security='.wp_create_nonce('cookieadmin_pro_admin_js_nonce').'", data, function(response) {
			console.log(response);
			});
			
		});');
	}

	static function close_geo_db_update_notice(){
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');

		if(!current_user_can('administrator')){
			wp_send_json_error( __('Sorry, but you do not have permissions to perform this action', 'cookieadmin'));
		}

		delete_option('cookieadmin_pro_geo_db_update_available');
		wp_send_json_success();
	}

	static function download_geo_target_country_db(){
		global $cookieadmin;

		if(!current_user_can( 'manage_options' )){
			wp_send_json_error(__('Invalid capability', 'cookieadmin'));
		}

		if(empty($cookieadmin['license']) || empty($cookieadmin['license']['license']) || empty($cookieadmin['license']['active'])){
			wp_send_json_error(__('License key required', 'cookieadmin'));
		}

		if(function_exists('set_time_limit')){
			@set_time_limit(0);
		}		

		$fastest_api_endpoint = self::get_fastest_endpoint();
		$api_endpoint = $fastest_api_endpoint . 'country-db.php?license='.$cookieadmin['license']['license'].'&url='.rawurlencode(site_url()).'&update=0';

		// Get the filename from the API
		$server_db_filename = self::check_for_db_update_available();
		if(empty($server_db_filename)){
			wp_send_json_error(__('Database file does not exists! please try after some time', 'cookieadmin'));
		}

		$server_db_filename = sanitize_file_name($server_db_filename);

		$wp_upload_dir_info = wp_upload_dir();
		$upload_dir = $wp_upload_dir_info['basedir'] . '/cookieadmin';

		if(!is_dir($upload_dir)){
			wp_mkdir_p($upload_dir);
		}
		
		// Adding htaccess file for security
		if(!file_exists($upload_dir .'/.htaccess')){
			file_put_contents($upload_dir .'/.htaccess', 'Deny from all');
			touch($upload_dir .'/index.html');
			touch($upload_dir .'/index.php');
		}

		$final_path = wp_normalize_path($upload_dir . '/' . $server_db_filename);

		if(file_exists($final_path)){
			$db_data = get_option('cookieadmin_pro_geo_target_db_download', []);
			wp_send_json_success(['msg' => __('Database is already up to date', 'cookieadmin'), 'time' => wp_date('Y-m-s h:i:s', $db_data['time'])]);
		}

		$tmp_path    = $final_path . '.tmp';
		$backup_path = $final_path . '.bak';

		// STEP 1: Download to TEMP file (NEVER live file)
		$response = wp_remote_get($api_endpoint, [
			'timeout'  => 60,
			'stream'   => true,
			'filename' => $tmp_path,
		]);

		if (is_wp_error($response)) {
			@unlink($tmp_path);
			wp_send_json_error($response->get_error_message());
		}

		$status_code = wp_remote_retrieve_response_code($response);

		if($status_code !== 200){
			@unlink($tmp_path);
			wp_send_json_error(sprintf(__('Status Code: %s', 'cookieadmin'), $status_code));
		}

		// STEP 2: Basic file validation
		if (!file_exists($tmp_path) || filesize($tmp_path) < 1024) {
			@unlink($tmp_path);
			wp_send_json_error(__('Downloaded file is invalid', 'cookieadmin'));
		}

		// STEP 4: Backup current live file ONLY AFTER validation
		if (file_exists($final_path)) {
			@rename($final_path, $backup_path);
		}

		// STEP 5: Promote temp → live
		if (!@rename($tmp_path, $final_path)) {

			// rollback
			if (file_exists($backup_path)) {
				@rename($backup_path, $final_path);
			}

			wp_send_json_error(__('Failed to replace database file', 'cookieadmin'));
		}

		// STEP 6: Cleanup backup AFTER success
		if (file_exists($backup_path)) {
			@unlink($backup_path);
		}

		// Checking if the downloaded file was a valid mmdb file
		if(!class_exists('\CookieAdminProMaxMind\Db\Reader')){
			include_once COOKIEADMIN_PRO_DIR . 'lib/MaxMind/autoloader.php';
		}

		try{
			$reader = new \CookieadminProMaxMind\Db\Reader($final_path);
			$metadata = $reader->metadata();
		}catch(\Exception $e){
			wp_send_json_error(sanitize_text_field(wp_unslash($e->getMessage())));
		}

		$db_data = ['filename' => $server_db_filename, 'time' => time()];
		update_option('cookieadmin_pro_geo_target_db_download', $db_data);

		wp_send_json_success(['msg' => __('Country database downloaded and updated successfully', 'cookieadmin'), 'time' => wp_date('Y-m-d h:i:s', $db_data['time'])]);
	}

	static function check_for_db_update_available(){
		global $cookieadmin;

		$fastest_api_endpoint = self::get_fastest_endpoint();
		$api_endpoint = 'https://a.softaculous.com/cookieadmin/' . 'country-db.php?license='.$cookieadmin['license']['license'].'&url='.rawurlencode(site_url()).'&update=1';

		$response = wp_remote_get($api_endpoint, ['timeout' => 60]);

		// Network or WP error
		if(is_wp_error($response)){
			return false;
		}

		// Get HTTP status code, body and content type to detect if it's valid response or error
		$status_code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);

		// If HTTP status is NOT 200, likely JSON error
		if($status_code !== 200){
			$json = json_decode($body, true);

			if(!empty($json['error'])){
				return false;
			}
			return false;
		}

		$body = json_decode($body, true);
		if(json_last_error() !== JSON_ERROR_NONE){
			return false;
		}

		if(empty($body)){
			return false;
		}

		return $body;
	}

	static function get_fastest_endpoint(){
		global $cookieadmin;
		
		$endpoints = get_transient('cookieadmin_fastest_endpoint');

		$mirror = 'https://s4.softaculous.com/a/cookieadmin/';

		if(empty($endpoints)){
			$res = wp_remote_get(COOKIEADMIN_API.'license.php?license='.$cookieadmin['license']['license'].'&url='.rawurlencode(site_url()));

			// Did we get a response ?
			if(!is_array($res)){
				return $mirror;
			}

			if(empty($res['body'])){
				return $mirror;
			}

			$body = json_decode($res['body'], true);

			if(empty($body['fast_mirrors'])){
				return $mirror;
			}
			
			$endpoints = $body['fast_mirrors'];
			
			if(empty($endpoints) || !is_array($endpoints)){
				return $mirror;
			}
		}
		
		$index = floor(rand(0, count($endpoints) - 1));

		if(empty($endpoints[$index])){
			return $mirror;
		}

		set_transient('cookieadmin_fastest_endpoint', $endpoints, 1800);

		$mirror = str_replace('a/softaculous', 'a/cookieadmin/', $endpoints[$index]);
		
		return $mirror;
	}
}
