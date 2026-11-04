<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

$content = array(
	'cookieadmin_layout' => array(
		'box' => 
			'<div class="cookieadmin_law_container" role="region" aria-label="[[cookie_consent]]" tabindex="-1" autofocus>
				<div class="cookieadmin_consent_inside">
					<p id="cookieadmin_notice_title"></p>
					<div class="cookieadmin_notice_con">
						<p id="cookieadmin_notice"></p>
					</div>
					<div class="cookieadmin_consent_btns">
						<button type="button" class="cookieadmin_btn cookieadmin_customize_btn" id="cookieadmin_customize_button">Customize</button>
						<button type="button" class="cookieadmin_btn cookieadmin_reject_btn" id="cookieadmin_reject_button">Reject All</button>
						<button type="button" class="cookieadmin_btn cookieadmin_accept_btn" id="cookieadmin_accept_button">Accept All</button>
					</div>
					[[banner_policy_links]]
					[[powered_by_html]]
				</div>
			</div>',
		
		'footer' => 
			'<div class="cookieadmin_law_container" role="region" aria-label="[[cookie_consent]]" tabindex="-1">
				<div class="cookieadmin_consent_inside">
					<p id="cookieadmin_notice_title"></p>
					<div class="cookieadmin_notice_group">
						<div class="cookieadmin_notice_con">
							<p id="cookieadmin_notice"></p>
						</div>
						<div class="cookieadmin_consent_btns">
							<button type="button" class="cookieadmin_btn cookieadmin_customize_btn" id="cookieadmin_customize_button">Customize</button>
							<button type="button" class="cookieadmin_btn cookieadmin_reject_btn" id="cookieadmin_reject_button">Reject All</button>
							<button type="button" class="cookieadmin_btn cookieadmin_accept_btn" id="cookieadmin_accept_button">Accept All</button>
						</div>
					</div>
					[[banner_policy_links]]
					[[powered_by_html]]
				</div>
			</div>'
			
	),
	'cookieadmin_modal' => array(
		'center' => 
			'<div class="cookieadmin_cookie_modal" role="dialog" aria-modal="true" aria-labelledby="cookieadmin_preference_title" tabindex="-1" autofocus>
				<div class="cookieadmin_mod_head">
					<span class="cookieadmin_preference_title" id="cookieadmin_preference_title" role="heading" aria-level="2"></span>
					<button type="button" class="cookieadmin_close_pref" aria-label="[[close]]"></button>
				</div>
				<div class="cookieadmin_details_wrapper">
					<div class="cookieadmin_preference_details">
						<div id="cookieadmin_preference" class="cookieadmin_preference"></div>
						[[dpdp_contact]]
					</div>
					<div id="cookieadmin_wrapper">
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-necessary-heading">
							<div class="cookieadmin_header"> 
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-necessary-container" aria-expanded="false" aria-controls="cookieadmin-necessary-list"><span class="stitle" id="cookieadmin-necessary-heading">[[necessary_cookies]]</span></button> 
								</span>
								<label class="cookieadmin_remark cookieadmin_act">[[remark_standard]]</label>								
							</div>
							<div class="cookieadmin_desc"> [[necessary_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-necessary" id="cookieadmin-necessary-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-functional-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-functional-container" aria-expanded="false" aria-controls="cookieadmin-functional-list"><span class="stitle" id="cookieadmin-functional-heading">[[functional_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-functional" aria-labelledby="cookieadmin-functional-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[functional_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[functional_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-functional" id="cookieadmin-functional-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-analytics-heading">
							<div class="cookieadmin_header">
								<span> 
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-analytics-container" aria-expanded="false" aria-controls="cookieadmin-analytics-list"><span class="stitle" id="cookieadmin-analytics-heading">[[analytical_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-analytics" aria-labelledby="cookieadmin-analytics-heading"> 
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[analytical_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[analytical_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-analytics" id="cookieadmin-analytics-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-marketing-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-marketing-container" aria-expanded="false" aria-controls="cookieadmin-marketing-list"><span class="stitle" id="cookieadmin-marketing-heading">[[advertisement_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-marketing" aria-labelledby="cookieadmin-marketing-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[advertisement_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[advertisement_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-marketing" id="cookieadmin-marketing-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-unclassified-heading" hidden>
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="un_c-container" aria-expanded="false" aria-controls="un_c-list"><span class="stitle" id="cookieadmin-unclassified-heading">[[unclassified_cookies]]</span></button>
								</span>
							</div>
							<div class="cookieadmin_desc"> [[unclassified_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list un_c" id="un_c-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
					</div>
				</div>
				<div class="cookieadmin_modal_footer">
					[[override_gpc]]
					<button type="button" class="cookieadmin_btn cookieadmin_reject_btn" id="cookieadmin_reject_modal_button">Reject All</button>
					<button type="button" class="cookieadmin_btn cookieadmin_save_btn" id="cookieadmin_prf_modal_button">Save My Preferences</button>
					<button type="button" id="cookieadmin_accept_modal_button" class="cookieadmin_btn cookieadmin_accept_btn">Accept All</button>
					<div class="cookieadmin_modal_footer_links">
					[[modal_policy_links]]
					[[powered_by_html]]
					</div>
				</div>
			</div>',
		
		'side' => '
			<div class="cookieadmin_cookie_modal" role="dialog" aria-modal="true" aria-labelledby="cookieadmin_preference_title" tabindex="-1" autofocus>
				<div class="cookieadmin_mod_head">
					<span class="cookieadmin_preference_title" id="cookieadmin_preference_title" role="heading" aria-level="2"></span> 
					<button type="button" class="cookieadmin_close_pref" aria-label="[[close]]"></button> 
				</div>
				<div class="cookieadmin_details_wrapper">
					<div class="cookieadmin_preference_details">
						<div id="cookieadmin_preference" class="cookieadmin_preference"> </div>
						[[dpdp_contact]]
					</div>
					<div id="cookieadmin_wrapper">
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-necessary-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-necessary-container" aria-expanded="false" aria-controls="cookieadmin-necessary-list"><span class="stitle" id="cookieadmin-necessary-heading">[[necessary_cookies]]</span></button>
								</span>
								<label class="cookieadmin_remark cookieadmin_act">[[remark_standard]]</label>
							</div>
							<div class="cookieadmin_desc"> [[necessary_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-necessary" id="cookieadmin-necessary-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-functional-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-functional-container" aria-expanded="false" aria-controls="cookieadmin-functional-list"><span class="stitle" id="cookieadmin-functional-heading">[[functional_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-functional" aria-labelledby="cookieadmin-functional-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[functional_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[functional_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-functional" id="cookieadmin-functional-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-analytics-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-analytics-container" aria-expanded="false" aria-controls="cookieadmin-analytics-list"><span class="stitle" id="cookieadmin-analytics-heading">[[analytical_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-analytics" aria-labelledby="cookieadmin-analytics-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[analytical_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[analytical_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-analytics" id="cookieadmin-analytics-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-marketing-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-marketing-container" aria-expanded="false" aria-controls="cookieadmin-marketing-list"><span class="stitle" id="cookieadmin-marketing-heading">[[advertisement_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-marketing" aria-labelledby="cookieadmin-marketing-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[advertisement_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[advertisement_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-marketing" id="cookieadmin-marketing-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-unclassified-heading" hidden>
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="un_c-container" aria-expanded="false" aria-controls="un_c-list"><span class="stitle" id="cookieadmin-unclassified-heading">[[unclassified_cookies]]</span></button>
								</span>
							</div>
							<div class="cookieadmin_desc"> [[unclassified_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list un_c" id="un_c-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
					</div>
				</div>
				<div class="cookieadmin_modal_footer">
					[[override_gpc]]
					<button type="button" class="cookieadmin_btn cookieadmin_reject_btn" id="cookieadmin_reject_modal_button">Reject All</button>
					<button type="button" class="cookieadmin_btn cookieadmin_save_btn" id="cookieadmin_prf_modal_button">Save My Preferences</button>
					<button type="button" class="cookieadmin_btn cookieadmin_accept_btn" id="cookieadmin_accept_modal_button">Accept All</button>
					<div class="cookieadmin_modal_footer_links">
					[[modal_policy_links]]
					[[powered_by_html]]
					</div>
				</div>
			</div>',
		
		'down' => 
			'<div class="cookieadmin_cookie_modal" role="dialog" aria-modal="true" aria-labelledby="cookieadmin_preference_title" tabindex="-1" autofocus>
				<div class="cookieadmin_mod_head">
					<span class="cookieadmin_preference_title" id="cookieadmin_preference_title" role="heading" aria-level="2"></span>
					<button type="button" class="cookieadmin_close_pref" aria-label="[[close]]"></button>
				</div>
				<div class="cookieadmin_details_wrapper">
					<div class="cookieadmin_preference_details">
						<div id="cookieadmin_preference" class="cookieadmin_preference"></div>
						[[dpdp_contact]]
					</div>
					<div id="cookieadmin_wrapper">
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-necessary-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-necessary-container" aria-expanded="false" aria-controls="cookieadmin-necessary-list"><span class="stitle" id="cookieadmin-necessary-heading">[[necessary_cookies]]</span></button>
								</span>
								<label class="cookieadmin_remark cookieadmin_act">[[remark_standard]]</label>
							</div>
							<div class="cookieadmin_desc"> [[necessary_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-necessary" id="cookieadmin-necessary-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-functional-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-functional-container" aria-expanded="false" aria-controls="cookieadmin-functional-list"><span class="stitle" id="cookieadmin-functional-heading">[[functional_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-functional" aria-labelledby="cookieadmin-functional-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[functional_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[functional_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-functional" id="cookieadmin-functional-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-analytics-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-analytics-container" aria-expanded="false" aria-controls="cookieadmin-analytics-list"><span class="stitle" id="cookieadmin-analytics-heading">[[analytical_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-analytics" aria-labelledby="cookieadmin-analytics-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[analytical_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[analytical_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-analytics" id="cookieadmin-analytics-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-marketing-heading">
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="cookieadmin-marketing-container" aria-expanded="false" aria-controls="cookieadmin-marketing-list"><span class="stitle" id="cookieadmin-marketing-heading">[[advertisement_cookies]]</span></button>
								</span>
								<label class="cookieadmin_toggle">
									<input type="checkbox" id="cookieadmin-marketing" aria-labelledby="cookieadmin-marketing-heading">
									<span class="cookieadmin_slider"></span>
									<span class="cookieadmin_sr_only">[[advertisement_cookies]]</span>
								</label>
							</div>
							<div class="cookieadmin_desc"> [[advertisement_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list cookieadmin-marketing" id="cookieadmin-marketing-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
						<div class="cookieadmin_types" role="region" aria-labelledby="cookieadmin-unclassified-heading" hidden>
							<div class="cookieadmin_header">
								<span>
									<button type="button" class="cookieadmin_show_pref_cookies" id="un_c-container" aria-expanded="false" aria-controls="un_c-list"><span class="stitle" id="cookieadmin-unclassified-heading">[[unclassified_cookies]]</span></button>
								</span>
							</div>
							<div class="cookieadmin_desc"> [[unclassified_cookies_desc]] </div>
							<div class="cookieadmin-modal-cookies-list un_c" id="un_c-list"><span class="cookieadmin-nocookie-cat">[[none]]</span></div>
						</div>
					</div>
				</div>
				<div class="cookieadmin_modal_footer">
					[[override_gpc]]
					<button type="button" class="cookieadmin_btn cookieadmin_reject_btn" id="cookieadmin_reject_modal_button">Reject All</button>
					<button type="button" class="cookieadmin_btn cookieadmin_save_btn" id="cookieadmin_prf_modal_button">Save My Preferences</button>
					<button type="button" class="cookieadmin_btn cookieadmin_accept_btn" id="cookieadmin_accept_modal_button">Accept All</button>
					<div class="cookieadmin_modal_footer_links">
					[[modal_policy_links]]
					[[powered_by_html]]
					</div>
				</div>
			</div>'
	),
	'cookieadmin_reconsent' => 
		'<div class="cookieadmin_reconsent_wrap">
			<button type="button" class="cookieadmin_re_consent">
				<img class="cookieadmin_reconsent_img" height="40" width="40" src="[[reconsent_icon_url]]" alt="[[reconsent]]">
			</button>
		</div>'
);
