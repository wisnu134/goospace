<?php

namespace CookieAdminPro\Admin;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class DPDP{

	static function dpdp_requests(){
		global $wpdb, $cookieadmin_msg, $cookieadmin_error;

		$settings = \CookieAdminPro\DPDP::get_settings();

		$table_name = $wpdb->prefix . 'cookieadmin_dpdp_requests';
		$current_tab = !empty($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'requests';

		if(!empty($_POST['cookieadmin_dpdp_save_settings'])){
			check_admin_referer('cookieadmin_dpdp_settings', 'cookieadmin_pro_security');

			$dpdp_settings = !empty($_POST['cookieadmin_dpdp']) ? map_deep(wp_unslash($_POST['cookieadmin_dpdp']), 'sanitize_text_field') : [];

			if(!empty($dpdp_settings['request_types']) && is_array($dpdp_settings['request_types'])){
				$all_types = array('access', 'correction', 'erasure', 'withdraw_consent', 'grievance', 'nomination');
				$selected = array_intersect($dpdp_settings['request_types'], $all_types);
				$dpdp_settings['request_types'] = array();
				foreach($all_types as $at){
					$dpdp_settings['request_types'][$at] = in_array($at, $selected, true) ? 1 : 0;
				}
			}else{
				$dpdp_settings['request_types'] = array();
			}

			if(!empty($settings['custom_page'])){
				$dpdp_settings['custom_page'] = $settings['custom_page'];
			}

			$dpdp_settings['exp_content'] = !empty($_POST['cookieadmin_dpdp_exp_content']) ? wp_kses_post(wp_unslash($_POST['cookieadmin_dpdp_exp_content'])) : '';
			$dpdp_settings['selected_page'] = !empty($_POST['cookieadmin_dpdp_form_page']) ? (int) sanitize_text_field(wp_unslash($_POST['cookieadmin_dpdp_form_page'])) : 0;
			$dpdp_settings['response_timeline'] = !empty($dpdp_settings['response_timeline']) ? max(1, min(90, (int) $dpdp_settings['response_timeline'])) : 30;
			$dpdp_settings['grievance_officer_email'] = !empty($dpdp_settings['grievance_officer_email']) ? sanitize_email($dpdp_settings['grievance_officer_email']) : '';

			if(!empty($dpdp_settings['enabled'])){
				if(empty($dpdp_settings['grievance_officer_name']) || empty($dpdp_settings['grievance_officer_email'])){
					$cookieadmin_error = __('Please provide the Grievance Officer name and a valid email to enable the DPDP portal', 'cookieadmin');
					$dpdp_settings['enabled'] = 0;
				}

				if(!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)){
					self::create_dpdp_table($table_name);
				}
			}

			if(($dpdp_settings['selected_page'] > 0) && ($dpdp_settings['selected_page'] !== (int) $settings['selected_page'])){
				$selected_page = get_post($dpdp_settings['selected_page']);

				if(!empty($selected_page) && !empty($selected_page->ID)){
					if(strpos($selected_page->post_content, '[cookieadmin_dpdp_request]') === false){
						$result = wp_update_post(array(
							'ID' => $selected_page->ID,
							'post_content' => $selected_page->post_content . '<!-- wp:shortcode -->[cookieadmin_dpdp_request]<!-- /wp:shortcode -->'
						));

						if($result instanceof WP_Error){
							$cookieadmin_error = __('Error updating the shorcode on selected page', 'cookieadmin');
						}
					}
				}
			}

			$prev_shortcode_page = !empty($settings['selected_page']) ? get_post((int) $settings['selected_page']) : 0;
			if(!empty($prev_shortcode_page) && ($dpdp_settings['selected_page'] !== (int) $settings['selected_page'])){
				if(!empty($prev_shortcode_page->post_content) && (strpos($prev_shortcode_page->post_content, '[cookieadmin_dpdp_request]') !== false)){
					$result = wp_update_post(array(
						'ID' => $prev_shortcode_page->ID,
						'post_content' => str_replace('<!-- wp:shortcode -->[cookieadmin_dpdp_request]<!-- /wp:shortcode -->', '', $prev_shortcode_page->post_content)
					));

					if($result instanceof WP_Error){
						$cookieadmin_error = __('Error updating the shorcode on selected page', 'cookieadmin');
					}
				}
			}

			if(empty($cookieadmin_error)){
				update_option('cookieadmin_dpdp_settings', $dpdp_settings);
				$cookieadmin_msg = __('Settings saved successfully', 'cookieadmin');
			}
		}

		if(!empty($_POST['cookieadmin_dpdp_bulk_action']) && !empty($_POST['cookieadmin_dpdp_ids'])){
			check_admin_referer('cookieadmin_pro_admin_nonce', 'cookieadmin_pro_security');

			$ids = map_deep(wp_unslash($_POST['cookieadmin_dpdp_ids']), 'intval');

			if($_POST['cookieadmin_dpdp_bulk_action'] === 'resolved'){
				foreach($ids as $id){
					$wpdb->update(
						$table_name,
						array('status' => 'Resolved', 'responded_at' => time()),
						array('id' => $id),
						array('%s', '%d'),
						array('%d')
					);
				}
				$cookieadmin_msg = __('Requests marked as resolved', 'cookieadmin');
			}
		}

		\CookieAdmin\Admin::header_theme(__('DPDP Data Principal Requests', 'cookieadmin'));

		echo '<div class="cookieadmin-nav-tabs">';

		$active_requests = ($current_tab === 'requests') ? ' nav-tab-active' : '';
		$active_settings = ($current_tab === 'settings') ? ' nav-tab-active' : '';

		echo '
				<a class="'.esc_attr($active_requests).'" href="'.esc_url(admin_url('admin.php?page=cookieadmin-dpdp&tab=requests')).'">'.esc_html__('Requests', 'cookieadmin').'</a>
				<a class="'.esc_attr($active_settings).'" href="'.esc_url(admin_url('admin.php?page=cookieadmin-dpdp&tab=settings')).'">'.esc_html__('Settings', 'cookieadmin').'</a>
			</div>';

		if($current_tab === 'settings'){
			self::render_settings_tab();
		}else{
			self::render_requests_tab($table_name);
		}

		\CookieAdmin\Admin::footer_theme();
	}

	static function create_dpdp_table($dpdp_table){
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$db_path = !defined('SITEPAD') ? ABSPATH . 'wp-admin/includes/upgrade.php' : ABSPATH . 'site-admin/includes/upgrade.php';
		require_once($db_path);

		$sql = 'CREATE TABLE ' . $dpdp_table . ' (
			id INT NOT NULL AUTO_INCREMENT,
			request_id VARCHAR(64) NOT NULL,
			user_email VARCHAR(100) NOT NULL,
			first_name VARCHAR(100) DEFAULT NULL,
			last_name VARCHAR(100) DEFAULT NULL,
			request_type VARCHAR(50) NOT NULL,
			request_details TEXT DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT \'Received\',
			created_at BIGINT NOT NULL,
			responded_at BIGINT DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY request_id (request_id)
		) ' . $charset_collate . ';';

		dbDelta($sql);
	}

	static function generate_dpdp_page(){

		$settings = get_option('cookieadmin_dpdp_settings', []);

		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
		if(!current_user_can('administrator')){
			wp_send_json_error(__('Invalid user', 'cookieadmin'));
		}

		if(!empty($settings['custom_page'])){
			$custom_page = get_post((int) $settings['custom_page']);
			if(!empty($custom_page && $custom_page->ID)){
				wp_send_json_error(__('Page already generated', 'cookieadmin'));
			}
		}

		$page_content = '<!-- wp:shortcode -->[cookieadmin_dpdp_request]<!-- /wp:shortcode -->';
		$page_title = __('Data Principal Request (DPDP)', 'cookieadmin');

		$post_args = array(
			'post_type' => 'page',
			'post_title' => $page_title,
			'post_content' => $page_content,
			'post_status' => 'publish',
		);

		$page_id = wp_insert_post($post_args, true);
		if(!($page_id instanceof WP_Error)){
			$permalink = get_permalink($page_id);
			if(!empty($permalink)){
				$settings['selected_page'] = $page_id;
				$settings['custom_page'] = $page_id;
				$settings['enabled'] = '1';
				update_option('cookieadmin_dpdp_settings', $settings);
				wp_send_json_success();
			}
		}

		wp_send_json_error(__('Something went wrong', 'cookieadmin'));
	}

	static function export_dpdp_requests(){
		global $wpdb;

		$cookieadmin_export_type = !empty($_REQUEST['cookieadmin_export_type']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_export_type'])) : '';

		if(!empty($cookieadmin_export_type) && $cookieadmin_export_type == 'dpdp_requests'){

			$table_name = esc_sql($wpdb->prefix . 'cookieadmin_dpdp_requests');

			if(!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)){
				echo -1;
				echo esc_html__('Configure DPDP settings', 'cookieadmin');
				wp_die();
			}

			$requests = $wpdb->get_results("SELECT request_id, user_email, first_name, last_name, request_type, request_details, status, created_at, responded_at FROM $table_name ORDER BY id DESC", ARRAY_A);

			if(!empty($requests)){
				foreach($requests as $rk => $request){
					$requests[$rk]['created_at'] = !empty($request['created_at']) ? wp_date('Y-m-d H:i:s', $request['created_at']) : '';
					$requests[$rk]['responded_at'] = !empty($request['responded_at']) ? wp_date('Y-m-d H:i:s', $request['responded_at']) : '';
				}
			}
		}

		if(empty($requests)){
			echo -1;
			echo esc_html__('No data to export', 'cookieadmin');
			wp_die();
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cookieadmin-dpdp-requests.csv');

		$output = fopen('php://output', 'w');

		fputcsv($output, array('Request ID', 'Email', 'First Name', 'Last Name', 'Request Type', 'Details', 'Status', 'Received', 'Responded'));

		foreach($requests as $request){
			fputcsv($output, $request);
		}

		fclose($output);

		wp_die();
	}

	static function get_dpdp_requests(){
		global $wpdb;

		$table_name = $wpdb->prefix . 'cookieadmin_dpdp_requests';
		
		if (!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)) {
			return array();
		}

		$dpdp_requests = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A);
		if (!empty($dpdp_requests) && is_array($dpdp_requests)){
			return $dpdp_requests;
		}

		return array();
	}

	static function render_requests_tab($table_name){
		global $cookieadmin_msg;

		$dpdp_requests = self::get_dpdp_requests();
		$request_types = \CookieAdminPro\DPDP::get_request_types();

		echo '<div class="cookieadmin_pro_consent-wrap" style="max-width: 85vw;">
			<form action="" method="post">
				<div class="cookieadmin_consent-contents">
					<div class="cookieadmin_consent">
						<div class="contents cookieadmin_manager">
							<div class="cookieadmin-setting cookieadmin-manager-consent-logs">';
							wp_nonce_field('cookieadmin_pro_admin_nonce', 'cookieadmin_pro_security');
							echo '<div class="cookieadmin-dns-table-operations">
									<div class="cookieadmin-dns-bulk-action">
										<select name="cookieadmin_dpdp_bulk_action">
											<option value="">'.esc_html__('Bulk Actions', 'cookieadmin').'</option>
											<option value="resolved">'.esc_html__('Mark as Processed', 'cookieadmin').'</option>
										</select>
										<input type="submit" class="cookieadmin-btn cookieadmin-btn-primary" value="'.esc_html__('Apply', 'cookieadmin').'">
									</div>
									<button type="button" class="cookieadmin-btn cookieadmin-btn-primary" id="cookieadmin_pro_export_dpdp_req">'
										.esc_html__('Export Requests (CSV)', 'cookieadmin').
									'</button>
								</div>
								<div class="cookieadmin-manager-scan-result">
									<table class="cookieadmin-table cookieadmin-dpdp-requests">
										<thead>
											<tr>
												<th style="width: 40px;"><input type="checkbox" id="cookieadmin_dpdp_select_all"></th>
												<th>'.esc_html__('Request ID', 'cookieadmin').'</th>
												<th>'.esc_html__('Email', 'cookieadmin').'</th>
												<th>'.esc_html__('Name', 'cookieadmin').'</th>
												<th>'.esc_html__('Request Type', 'cookieadmin').'</th>
												<th>'.esc_html__('Status', 'cookieadmin').'</th>
												<th>'.esc_html__('Received', 'cookieadmin').'</th>
											</tr>
										</thead>
										<tbody>';

										if(empty($dpdp_requests)){
											echo '<tr><td colspan="8" class="cookieadmin-dpdp-empty">'.esc_html__('No requests found', 'cookieadmin').'</td></tr>';
										}else{
											foreach($dpdp_requests as $req){
												$status_class = (strtolower($req['status']) === 'resolved') ? 'success' : 'warning';
											echo '<tr>
												<td><input type="checkbox" name="cookieadmin_dpdp_ids[]" value="'.esc_attr($req['id']).'"></td>
												<td>'.esc_html($req['request_id']).'</td>
												<td>'.esc_html($req['user_email']).'</td>
												<td>'.esc_html($req['first_name'].' '.$req['last_name']).'</td>
												<td>'.esc_html($request_types[$req['request_type']]).'</td>
												<td>
													<span class="cookieadmin-badge cookieadmin-'.esc_attr($status_class).'">'.esc_html(ucfirst($req['status'])).'</span>'
													.((!empty($req['responded_at']) && (strtolower($req['status']) === 'resolved'))
													? '<br /><span>'.sprintf(esc_html__('%s ago', 'cookieadmin'), human_time_diff($req['responded_at'])).'</span>' : '').'
												</td>
												<td>'.esc_html(date('j M Y H:i:s', $req['created_at'])).'</td>
											</tr>';
											}
										}
										echo '</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>';
	}

	static function render_settings_tab(){
		global $cookieadmin;

		$settings = \CookieAdminPro\DPDP::get_settings();

		if(empty($settings['exp_content'])){
			$settings['exp_content'] = __('Under the Digital Personal Data Protection Act, 2023 (India), you have the right to access, correct or erase your personal data, withdraw your consent, nominate a representative to exercise your rights, and raise a grievance with our Grievance Officer. Submit the form below and we will respond within our published timeline.', 'cookieadmin');
		}

		$published_pages = get_pages();
		$custom_page = !empty($settings['custom_page']) ? get_post((int) $settings['custom_page']) : '';
		$preview_page_url = !empty($settings['selected_page']) ? get_permalink((int) $settings['selected_page']) : '';
		$request_types = \CookieAdminPro\DPDP::get_request_types();

		echo '<div class="cookieadmin_consent-wrap">
			<form method="post" id="cookieadmin_dpdp_settings_form">
				<div class="cookieadmin-card cookieadmin-mt-16" cookieadmin-pro-only="1">
					<div class="cookieadmin-card-header">
						<span class="cookieadmin-card-title"><span class="dashicons dashicons-admin-settings"></span>'.esc_html__('DPDP Portal Settings', 'cookieadmin').'</span>
					</div>
					<div class="cookieadmin-card-body">
						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Enable DPDP Data Principal Request Portal', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Enable this setting to accept and manage Data Principal requests under the DPDP Act, 2023', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin-toggle-wrap">
									<input name="cookieadmin_dpdp[enabled]" type="checkbox" value="1" '.(checked(!empty($settings['enabled']), true, false)).'>
									<div class="cookieadmin-toggle-track">
										<div class="cookieadmin-toggle-thumb"></div>
									</div>
								</label>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Grievance Officer Name', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Name of the person responsible for grievance redressal under Section 13(2) of the DPDP Act', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<input type="text" name="cookieadmin_dpdp[grievance_officer_name]" style="width:40%;" value="'.esc_attr($settings['grievance_officer_name']).'" required>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Grievance Officer Email', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Email of the person responsible for grievance redressal under Section 13(2) of the DPDP Act', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<input type="email" name="cookieadmin_dpdp[grievance_officer_email]" style="width:40%;" value="'.esc_attr($settings['grievance_officer_email']).'" required>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Response Timeline (days)', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Published timeline within which Data Principal requests will be addressed. Maximum 90 days as per the DPDP Rules.', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<input type="number" name="cookieadmin_dpdp[response_timeline]" style="max-width:80px;" min="1" max="90" value="'.esc_attr($settings['response_timeline']).'">
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Request Types', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Select the Data Principal rights that can be exercised through the request form', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">';
								foreach($request_types as $tk => $tl){
									echo '<label class="cookieadmin-horizontal" style="cursor:pointer;font-weight:400;margin-right:12px;">
										<input type="checkbox" name="cookieadmin_dpdp[request_types][]" value="'.esc_attr($tk).'" '.(checked(!empty($settings['request_types'][$tk]), true, false)).'> '.esc_html($tl).'
									</label>';
								}
							echo '</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Explanation Text', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('A concise description of the Data Principal rights shown above the request form', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<textarea name="cookieadmin_dpdp_exp_content" id="cookieadmin_dpdp_exp_content" rows="5" cols="100">'.esc_html($settings['exp_content']).'</textarea>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Select Page', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Select the page to show the Data Principal request form', 'cookieadmin').'"></span>
							</label>
							<div class="cookieadmin-setting-contents">
								<select name="cookieadmin_dpdp_form_page" id="cookieadmin_dpdp_select_page">
									<option value="">'.esc_html__('--- None ---', 'cookieadmin').'</option>';
									if(!empty($published_pages) && is_array($published_pages)){
										foreach($published_pages as $page){
											echo '<option value="'.esc_attr($page->ID).'" '.selected((int) $settings['selected_page'], $page->ID, false).'>'.esc_html($page->post_title).'</option>';
										}
									}
								echo '</select>'.(empty($settings['custom_page']) || empty($custom_page)
								? '<span>'.esc_html__('OR', 'cookieadmin').'</span>
								<input type="button" class="cookieadmin-btn" id="cookieadmin_dpdp_generate_page" value="'.esc_html__('Generate New Page', 'cookieadmin').'">' : '').'
								<span class="cookieadmin_dpdp_page_preview" '.(empty($preview_page_url) ? 'style="display:none;"' : '').'>
									<span class="dashicons dashicons-external"></span>&nbsp;
									<a href="'.(!empty($preview_page_url) ? esc_attr($preview_page_url) : '#').'" target="__blank">'.esc_html__('Preview DPDP request form', 'cookieadmin').'</a>
								</span>
								<span class="cookieadmin_desc cookieadmin-dns-shortcode-box">'.sprintf(
									esc_html__('The shortcode %s must be present on the selected page in order to display the Data Principal request form correctly.', 'cookieadmin'),
									'<span><strong>[cookieadmin_dpdp_request]</strong></span>'
								).'</span>
							</div>
						</div>
					</div>
				</div>';

				wp_nonce_field('cookieadmin_dpdp_settings', 'cookieadmin_pro_security');

				echo '
				<div class="cookieadmin-save-bar">
					<input type="submit" name="cookieadmin_dpdp_save_settings" class="cookieadmin-btn cookieadmin-btn-primary" value="'.esc_html__('Save Settings', 'cookieadmin').'">
				</div>
			</form>
		</div>';
	}

	static function dpdp_policies(){
		return [
  			'cookieadmin_dpdp' => array (
				'cookieadmin_layout' => 'box',
				'cookieadmin_modal' => 'center',
				'cookieadmin_position' => 'bottom_left',
				'cookieadmin_days' => '365',
				'cookieadmin_notice_title' => __('We Value Your Privacy', 'cookieadmin'),
				'cookieadmin_notice' => __('Under India\'s Digital Personal Data Protection Act, 2023 (DPDP Act), we seek your consent before processing your personal data. You may accept all, reject all, or customize categories. Consent is voluntary — refusing non-essential cookies will not affect the core functioning of the website. You may withdraw consent or exercise your rights (access, correction, erasure, grievance redressal, nomination) as easily as you gave consent.', 'cookieadmin'),
				'cookieadmin_preference_title' => __('Manage Your Consent Preferences', 'cookieadmin'),
				'cookieadmin_preference' => __('We use cookies to process your personal data under the DPDP Act, 2023. <b>Necessary</b> cookies are essential for the website to function and are always active. <b>Functional</b> cookies remember your choices and enable enhanced features. <b>Analytics</b> cookies help us understand how visitors use our website. <b>Advertisement</b> cookies deliver personalized ads based on your visits. You have the right to withdraw your consent at any time, as easily as you gave it, and to approach our Grievance Officer with any data protection concern.', 'cookieadmin'),
				'reConsent_title' => __('Modify Cookie Preferences', 'cookieadmin'),
				'cookieadmin_notice_title_color' => '#1f2937',
				'cookieadmin_notice_color' => '#1f2937',
				'cookieadmin_consent_inside_bg_color' => '#ffffff',
				'cookieadmin_consent_inside_border_color' => '#ffffff',
				'cookieadmin_preference_title_color' => '#1f2937',
				'cookieadmin_details_wrapper_color' => '#1f2937',
				'cookieadmin_cookie_modal_bg_color' => '#ffffff',
				'cookieadmin_cookie_modal_border_color' => '#ffffff',
				'cookieadmin_customize_btn' => __('Customize', 'cookieadmin'),
				'cookieadmin_customize_btn_color' => '#1f2937',
				'cookieadmin_customize_btn_bg_color' => '#ffffff',
				'cookieadmin_reject_btn' => __('Reject All', 'cookieadmin'),
				'cookieadmin_reject_btn_color' => '#1f2937',
				'cookieadmin_reject_btn_bg_color' => '#ffffff',
				'cookieadmin_accept_btn' => __('Accept All', 'cookieadmin'),
				'cookieadmin_accept_btn_color' => '#ffffff',
				'cookieadmin_accept_btn_bg_color' => '#1863dc',
				'cookieadmin_save_btn' => __('Save Preferences', 'cookieadmin'),
				'cookieadmin_save_btn_color' => '#ffffff',
				'cookieadmin_save_btn_bg_color' => '#1863dc',
				'cookieadmin_slider_off_bg_color' => '#808080',
				'cookieadmin_slider_on_bg_color' => '#3582C4',
				'cookieadmin_links_color' => '#1863dc',
			),
		];
	}
}
