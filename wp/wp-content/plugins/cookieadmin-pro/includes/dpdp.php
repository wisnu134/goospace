<?php

namespace CookieAdminPro;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class DPDP{

	static function get_settings(){

		$defaults = array(
			'enabled' => 0,
			'grievance_officer_name' => '',
			'grievance_officer_email' => '',
			'response_timeline' => 30,
			'selected_page' => 0,
			'exp_content' => '',
			'request_types' => array(
				'access' => 1,
				'correction' => 1,
				'erasure' => 1,
				'withdraw_consent' => 1,
				'grievance' => 1,
				'nomination' => 1,
			),
			'custom_page' => 0,
		);

		$settings = get_option('cookieadmin_dpdp_settings', array());
		
		if(empty($settings) || !is_array($settings)){
			return $defaults;
		}

		$settings = array_merge($defaults, $settings);
		$settings['request_types'] = array_merge($defaults['request_types'], !empty($settings['request_types']) && is_array($settings['request_types']) ? $settings['request_types'] : array());

		return $settings;
	}

	static function is_enabled(){
		
		$settings = self::get_settings();
		
		if(empty($settings['enabled'])){
			return false;
		}

		if(empty($settings['grievance_officer_name']) || !is_email($settings['grievance_officer_email'])){
			return false;
		}

		return true;
	}

	static function is_active_law(){
		return (get_option('cookieadmin_law', 'cookieadmin_gdpr') === 'cookieadmin_dpdp');
	}

	static function get_request_types(){

		return array(
			'access' => __('Access my data', 'cookieadmin'),
			'correction' => __('Correct my data', 'cookieadmin'),
			'erasure' => __('Erase my data', 'cookieadmin'),
			'withdraw_consent' => __('Withdraw my consent', 'cookieadmin'),
			'grievance' => __('File a grievance', 'cookieadmin'),
			'nomination' => __('Nominate a representative', 'cookieadmin'),
		);
	}

	static function submit_dpdp_request(){
		global $wpdb, $cookieadmin;

		if(empty($_POST['cookieadmin_dpdp_req'])){
			wp_send_json_error(__('Invalid request', 'cookieadmin'));
		}

		$form_data = json_decode(sanitize_text_field(wp_unslash($_POST['cookieadmin_dpdp_req'])), true);

		if(empty($form_data) || !is_array($form_data)){
			wp_send_json_error(__('Invalid request', 'cookieadmin'));
		}

		$settings = self::get_settings();

		if(empty($settings['enabled'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_disabled_err'], 'dpdp_disabled_err')));
		}

		if(empty($form_data['confirmation'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_confirm_err'], 'dpdp_confirm_err')));
		}

		if(
			(empty($form_data['first_name']) || empty($form_data['last_name']) || empty($form_data['email']) || empty($form_data['request_type']))
		){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_empty_fields_err'], 'dpdp_empty_fields_err')));
		}

		if(!preg_match('/^[\p{L}\s\'-]{2,50}$/u', $form_data['first_name']) || !preg_match('/^[\p{L}\s\'-]{2,50}$/u', $form_data['last_name'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_name_err'], 'dpdp_name_err')));
		}

		if(!is_email($form_data['email'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_email_err'], 'dpdp_email_err')));
		}

		$form_data['email'] = sanitize_email($form_data['email']);

		$allowed_types = array();
		foreach($settings['request_types'] as $rk => $rv){
			if(!empty($rv)){
				$allowed_types[] = $rk;
			}
		}

		if(!in_array($form_data['request_type'], $allowed_types, true)){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_type_err'], 'dpdp_type_err')));
		}

		$table_name = $wpdb->prefix . 'cookieadmin_dpdp_requests';

		if(!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_submission_err'], 'dpdp_submission_err')));
		}

		$email_exist = $wpdb->get_var(
			$wpdb->prepare("SELECT user_email FROM $table_name WHERE user_email = %s AND request_type = %s AND status = 'Received'", trim($form_data['email']), $form_data['request_type'])
		);

		if(!empty($email_exist)){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_request_in_progress'], 'dpdp_request_in_progress')));
		}

		$request_id = 'dpdp_' . strtolower(wp_generate_password(20, false, false));

		$insert = array(
			'request_id' => $request_id,
			'user_email' => $form_data['email'],
			'first_name' => $form_data['first_name'],
			'last_name' => $form_data['last_name'],
			'request_type' => $form_data['request_type'],
			'request_details' => !empty($form_data['details']) ? sanitize_textarea_field($form_data['details']) : '',
			'status' => 'Received',
			'created_at' => time(),
		);

		$inserted = $wpdb->insert(
			$table_name,
			$insert,
			array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
		);

		if($inserted === false){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dpdp_submission_err'], 'dpdp_submission_err')));
		}

		wp_send_json_success(esc_html(cookieadmin__($cookieadmin['default']['dpdp_submission_success'], 'dpdp_submission_success')));
	}

	static function get_dpdp_shortcode_html(){
		global $cookieadmin;

		$settings = self::get_settings();
		$types = self::get_request_types();

		$output = '<div class="cookieadmin-dpdp-form">
			<div class="cookieadmin-dpdp-form-header">
				<h3>'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_heading'], 'dpdp_heading')).'</h3>
			</div>
			<form method="post" id="cookieadmin_dpdp_form" onsubmit="cookieadmin_pro_submit_dpdp_form(event)">

				<div id="cookieadmin-dpdp-form-error" style="display:none;"></div>

				<div class="cookieadmin-dpdp-text">
					'.(!empty($settings['exp_content']) ? wp_kses_post($settings['exp_content'])
					: esc_html__('Under the Digital Personal Data Protection Act, 2023 (India), you have the right to access, correct or erase your personal data, withdraw your consent, nominate a representative to exercise your rights, and raise a grievance with our Grievance Officer. Submit the form below and we will respond within our published timeline.', 'cookieadmin')).'
				</div>';

				if(!empty($settings['grievance_officer_name']) || !empty($settings['grievance_officer_email'])){
					$output .= '<div class="cookieadmin-dpdp-officer">
						<strong>'.esc_html__('Grievance Officer', 'cookieadmin').':</strong> '
						.(!empty($settings['grievance_officer_name']) ? esc_html($settings['grievance_officer_name']) : '').
						(!empty($settings['grievance_officer_email']) ? ' &mdash; <a href="mailto:'.esc_attr($settings['grievance_officer_email']).'">'.esc_html($settings['grievance_officer_email']).'</a>' : '').
					'</div>';
				}

				$output .= '<div class="cookieadmin-dpdp-form-field">
					<label for="cookieadmin_dpdp_first_name">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_fname'], 'dpdp_fname')).'<span class="cookieadmin-dpdp-req-input">*</span></label>
					<input type="text" name="cookieadmin_dpdp_first_name" id="cookieadmin_dpdp_first_name" required>
				</div>

				<div class="cookieadmin-dpdp-form-field">
					<label for="cookieadmin_dpdp_last_name">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_lname'], 'dpdp_lname')).'<span class="cookieadmin-dpdp-req-input">*</span></label>
					<input type="text" name="cookieadmin_dpdp_last_name" id="cookieadmin_dpdp_last_name" required>
				</div>

				<div class="cookieadmin-dpdp-form-field">
					<label for="cookieadmin_dpdp_email">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_email'], 'dpdp_email')).'<span class="cookieadmin-dpdp-req-input">*</span></label>
					<input type="email" name="cookieadmin_dpdp_email" id="cookieadmin_dpdp_email" required>
				</div>';

				$output .= '<div class="cookieadmin-dpdp-form-field">
					<label for="cookieadmin_dpdp_request_type">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_request_type'], 'dpdp_request_type')).'<span class="cookieadmin-dpdp-req-input">*</span></label>
					<select name="cookieadmin_dpdp_request_type" id="cookieadmin_dpdp_request_type" required>
						<option value="">'.esc_html__('-- Select Request Type --', 'cookieadmin').'</option>';

						foreach($types as $tk => $tl){
							if(!empty($settings['request_types'][$tk])){
								$output .= '<option value="'.esc_attr($tk).'">'.esc_html($tl).'</option>';
							}
						}

				$output .= '</select>
				</div>

				<div class="cookieadmin-dpdp-form-field">
					<label for="cookieadmin_dpdp_details">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_details'], 'dpdp_details')).'</label>
					<textarea name="cookieadmin_dpdp_details" id="cookieadmin_dpdp_details" rows="5" cols="100"></textarea>
				</div>

				<div class="cookieadmin-dpdp-form-field">
					<span><input type="checkbox" name="cookieadmin_dpdp_confirmation" id="cookieadmin_dpdp_confirmation" required>
					<span class="cookieadmin-dpdp-req-input">*</span>'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_confirm_msg'], 'dpdp_confirm_msg')).'</span>
				</div>
				<div class="cookieadmin-dpdp-form-field cookieadmin-dpdp-save-btn" style="flex-direction:row;gap:10px;align-items:center;">
					<input type="submit" name="cookieadmin_dpdp_submit" class="button button-primary btn btn-primary wp-element-button cookieadmin_dpdp_submit" id="cookieadmin_dpdp_submit" value="'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_submit'], 'dpdp_submit')).'">
					<span class="cookieadmin-dpdp-spinner"></span>
				</div>
				<div id="cookieadmin-dpdp-form-success" style="display:none;"></div>
			</form>
		</div>';

		return $output;
	}

	static function form_script(){
		$nonce = wp_create_nonce('cookieadmin_pro_js_nonce');
		$ajax_url = admin_url('admin-ajax.php');
		return "
			// Submit DPDP Data Principal request
			function cookieadmin_pro_submit_dpdp_form(e){
				e.preventDefault();

				if(typeof cookieadmin_pro_vars === 'undefined' || !cookieadmin_pro_vars.ajax_url){
					return;
				}

				var success_elm = document.getElementById('cookieadmin-dpdp-form-success'),
				error_elm = document.getElementById('cookieadmin-dpdp-form-error'),
				spinner = document.querySelector('.cookieadmin-dpdp-spinner'),
				submit_btn = document.querySelector('.cookieadmin_dpdp_submit');

				if(success_elm && error_elm){
					success_elm.style.display = 'none';
					error_elm.style.display = 'none';
				}

				spinner.style.display = 'inline-block';
				submit_btn.disabled = true;

				var form_element = e.target;
				var form_data = {
					confirmation : '',
					first_name : '',
					last_name : '',
					email : '',
					request_type : '',
					details : ''
				};

				for(let key in form_data){
					form_data[key] = form_element.elements['cookieadmin_dpdp_' + key] ? form_element.elements['cookieadmin_dpdp_' + key].value : '';
				}

				form_data.confirmation = form_element.elements['cookieadmin_dpdp_confirmation'] && form_element.elements['cookieadmin_dpdp_confirmation'].checked ? '1' : '';

				const data = 'action=cookieadmin_pro_ajax_handler&cookieadmin_act=submit_dpdp_request' +
					'&cookieadmin_pro_security={$nonce}&cookieadmin_dpdp_req=' + encodeURIComponent(JSON.stringify(form_data));

				const xhttp = new XMLHttpRequest();

				xhttp.open('POST', '{$ajax_url}');

				xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

				xhttp.onload = function(){
					if(this.status === 200){
						try {
							var response = JSON.parse(this.responseText);
							if(response.success){
								if(success_elm){
									success_elm.innerText = response.data;
									success_elm.style.display = 'flex';
									form_element.reset();
								}
							}else{
								if(error_elm){
									error_elm.innerText = response.data;
									error_elm.style.display = 'flex';
								}
							}
						} catch (err) {
							console.log(err);
						}
					}

					spinner.style.display = 'none';
					submit_btn.disabled = false;
				}

				xhttp.onerror = function(){
					console.log('Ajax request failed');
					submit_btn.disabled = false;
				}

				xhttp.send(data);

				return true;
			}
		";
	}

	static function form_style(){
		return '.cookieadmin-dpdp-form{padding:10px 20px;max-width:600px;border:.5px solid #cecece;border-radius:5px}#cookieadmin_dpdp_form{display:flex;flex-direction:column;gap:15px}#cookieadmin_dpdp_form .cookieadmin-dpdp-form-field{display:flex;flex-direction:column;gap:3px}.cookieadmin-dpdp-form-field input[type="text"],.cookieadmin-dpdp-form-field input[type="email"],.cookieadmin-dpdp-form-field select,.cookieadmin-dpdp-form-field textarea{padding:5px;max-width:400px}.cookieadmin-dpdp-form-field input[type="submit"]{padding:8px 15px;width:fit-content}#cookieadmin-dpdp-form-error{padding:10px;color:#fa3d3d;border-left:3.5px solid #fa3d3d;background-color:#fef2f2}#cookieadmin-dpdp-form-success{padding:10px;text-align:center}.cookieadmin-dpdp-req-input{margin:5px;color:red}.cookieadmin-dpdp-form-header{text-align:center}.cookieadmin-dpdp-text{text-align:justify}.cookieadmin-dpdp-officer{padding:8px 10px;background:#f6f8fa;border-radius:4px;font-size:14px}.cookieadmin_dpdp_submit{cursor:pointer;}
		.cookieadmin-dpdp-spinner {
			display:none;
			width: 1em;
			height: 1em;
			border: 2px solid transparent;
			border-top-color: currentColor;
			border-radius: 50%;
			animation: cookieadmin-dpdp-spin .6s linear infinite;
		}

		@keyframes cookieadmin-dpdp-spin {
			to {
				transform: rotate(360deg);
			}
		}';
	}

	static function banner_contact_html($html = ''){

		if(!self::is_active_law() || !self::is_enabled()){
			return $html;
		}

		global $cookieadmin;

		$settings = self::get_settings();

		$html = '<div id="cookieadmin_dpdp_contact">';

		if(!empty($settings['grievance_officer_name']) || !empty($settings['grievance_officer_email'])){
			$html .= '<div>
				<strong>'.esc_html__('Grievance Officer', 'cookieadmin').':</strong> '
				.(!empty($settings['grievance_officer_name']) ? esc_html($settings['grievance_officer_name']) : '')
				.(!empty($settings['grievance_officer_email']) ? ' &mdash; <a href="mailto:'.esc_attr($settings['grievance_officer_email']).'">'.esc_html($settings['grievance_officer_email']).'</a>' : '').
			'</div>';
		}
		
		$page_url = !empty($settings['selected_page']) ? get_permalink((int) $settings['selected_page']) : '';
		
		if(!empty($page_url)){
			$html .= '<a href="'.esc_url($page_url).'">'.esc_html(cookieadmin__($cookieadmin['default']['dpdp_exercise_rights'], 'dpdp_exercise_rights')).'</a>';
		}

		$html .= '</div>';

		return $html;
	}

	static function geo_law_options($laws = array()){

		$laws['cookieadmin_dpdp'] = __('DPDP Act (India)', 'cookieadmin');

		return $laws;
	}

	static function enqueue_form_assets(){

		$form_script = self::form_script();
		wp_add_inline_script('cookieadmin_pro_js', $form_script);

		wp_register_style('cookieadmin-dpdp-form', false);
		wp_enqueue_style('cookieadmin-dpdp-form');

		$form_style = self::form_style();
		wp_add_inline_style('cookieadmin-dpdp-form', $form_style);
	}
}
