(function($) {

	var rulesData = [];
	var editingRuleId = null;

	$(document).ready(function() {
		initGeoPage();
	});

	function initGeoPage() {
		var initialRules = (window.cookieadmin_geo_vars && window.cookieadmin_geo_vars.rules) ? window.cookieadmin_geo_vars.rules : [];
		
		rulesData = JSON.parse(JSON.stringify(initialRules));
		updateEmptyState();
		bindGlobalEvents();
	}

	function bindGlobalEvents() {

		// Download Country database file
		$(document).on('click', '#cookieadmin-geo-download-db-btn', function(){
			
			let jEle = $(this);
			if(jEle.attr('disabled')){
				return;
			}

			let old_text = jEle.val();
			jEle.val('Downloading Database...');
			jEle.attr('disabled', true);

			var data = {
				action: 'cookieadmin_pro_ajax_handler',
				cookieadmin_act: 'download_geo_target_country_db',
				cookieadmin_pro_security: cookieadmin_geo_vars.nonce,
			};

			$.post(cookieadmin_geo_vars.ajax_url, data, function(response){
				if(response.success){
					var JElm1 = $('.cookieadmin-geo-db-avl');
					var JElm2 = $('.cookieadmin-geo-no-db');
					
					JElm2.hide();
					JElm1.find('span').text(response.data.time);
					JElm1.show();
					showNotice('success', response.data.msg);
				}else{
					showNotice('error', response.data);
				}
				jEle.val(old_text);
				jEle.attr('disabled', false);
			}).fail(function(){
				showNotice('error', 'Download Failed');
				jEle.val(old_text);
				jEle.attr('disabled', false);
			});
		});

		// Modal close button (uses existing cookieadmin close button class)
		$(document).on('click', '#cookieadmin_geo_modal .cookieadmin_dialog_modal_close_btn, #cookieadmin_geo_card_cancel', function() {
			hideCardForm();
		});

		// Click outside modal container to close
		$(document).on('mousedown', function(e) {
			var $modal = $('#cookieadmin_geo_modal');
			if ($modal.hasClass('active') && !$modal.find('.cookieadmin_modal-container').is(e.target) && $modal.find('.cookieadmin_modal-container').has(e.target).length === 0) {
				hideCardForm();
			}
		});

		// Card Cancel button
		$(document).on('click', '#cookieadmin_geo_card_cancel', function() {
			hideCardForm();
		});

		// Card Save button
		$(document).on('click', '#cookieadmin_geo_card_save', function() {
			saveCardForm(this);
		});

		// Escape key to close modal
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#cookieadmin_geo_modal').hasClass('active')) {
				hideCardForm();
			}
		});

		// Preset buttons
		$('.cookieadmin-geo-preset-btn').on('click', function() {
			var preset = $(this).data('preset');
			if(preset === 'custom'){
				showCardForm();
			}else{
				addPresetRule(preset);
			}
		});

		// Enable toggle
		$('#cookieadmin_geo_enabled_toggle').on('change', function() {
			toggleGeoEnabled($(this).is(':checked'));
		});

		// Card form country add
		$('#cookieadmin_geo_card_country_select').on('change', function() {
			onCountryAdd($(this));
		});

		// Continent shortcut buttons
		$('.cookieadmin-geo-shortcut-btn').on('click', function() {
			var continent = $(this).data('continent');
			addContinentCountries(continent);
		});

		// Card form country tag removes (event delegation)
		$('#cookieadmin_geo_card_tags').on('click', '.cookieadmin-geo-country-tag-remove', function() {
			$(this).closest('.cookieadmin-geo-country-tag').remove();
		});

		// Bind toggle events on card form
		bindToggleEvents();

		// Table actions (event delegation)
		$('#cookieadmin_geo_table_wrap').on('click', function(e) {
			var $target = $(e.target);
			var $editBtn = $target.closest('.cookieadmin-geo-table-edit');
			var $deleteBtn = $target.closest('.cookieadmin-geo-table-delete');

			if ($editBtn.length) {
				var ruleId = $editBtn.data('rule-id');
				editRule(ruleId);
			} else if ($deleteBtn.length) {
				var ruleId = $deleteBtn.data('rule-id');
				if (!confirm(cookieadmin_geo_vars.lang.delete_confirm)) {
					return;
				}

				updateEmptyState();

				// Auto-save to database
				saveRule(ruleId, $deleteBtn);
			}
		});
	}

	function renderTable() {
		var $tableWrap = $('#cookieadmin_geo_table_wrap');
		if (!$tableWrap.length) return;

		var countries = window.cookieadmin_geo_vars.countries || {};
		var continents = window.cookieadmin_geo_vars.continents || {};
		var laws = window.cookieadmin_geo_vars.laws || {};

		if (Object.keys(rulesData).length === 0) {
			$tableWrap.html('');
			return;
		}
		
		let rule_count = Object.keys(rulesData).length;

		var html = '';
		html += '<div class="cookieadmin-card">';
		html += '<div class="cookieadmin-card-header">';
		html += '<span class="cookieadmin-card-title"><span class="dashicons dashicons-admin-site"></span>' + escHtml(cookieadmin_geo_vars.lang.table_title) + '</span>';
		html += '<div style="display:flex; justify-content:center; gap:5px; align-items:center;"><span class="cookieadmin-badge cookieadmin-info">' + escHtml(rule_count) + ' ' + escHtml(rule_count === 1 ? cookieadmin_geo_vars.lang.rule_singular : cookieadmin_geo_vars.lang.rule_plural) + '</span><span class="spinner" style="margin:0; display:none;"></span></div>';
		html += '</div>';
		html += '<div class="cookieadmin-card-body" style="padding:0;">';
		html += '<table class="cookieadmin-table">';
		html += '<thead><tr>';
		html += '<th style="width:20%;">' + escHtml(cookieadmin_geo_vars.lang.rule_name) + '</th>';
		html += '<th style="width:10%;">' + (cookieadmin_geo_vars.lang.law_type ? escHtml(cookieadmin_geo_vars.lang.law_type) : 'None') + '</th>';
		html += '<th style="width:25%;">' + escHtml(cookieadmin_geo_vars.lang.countries) + '</th>';
		html += '<th style="width:7%;">' + escHtml(cookieadmin_geo_vars.lang.block_scripts_short) + '</th>';
		html += '<th style="width:7%;">' + escHtml(cookieadmin_geo_vars.lang.content_blocking_short) + '</th>';
		html += '<th style="width:7%;">' + escHtml(cookieadmin_geo_vars.lang.google_consent_mode_short) + '</th>';
		html += '<th style="width:7%;">' + escHtml(cookieadmin_geo_vars.lang.clarity_consent_short) + '</th>';
		html += '<th style="width:7%;">Actions</th>';
		html += '</tr></thead>';
		html += '<tbody>';

		$.each(rulesData, function(index, rule) {
			var ruleId = index;
			var ruleName = rule.name || '';
			var ruleLaw = rule.law || '';
			var ruleLawLabel = laws[ruleLaw] || ruleLaw;
			var ruleCountries = rule.countries || [];

			// Country badges
			var countryBadges = '';
			var displayCount = 0;
			var totalCount = 0;
			var hasShortcut = false;
			
			// Count total countries (expand shortcuts)
			$.each(ruleCountries, function(i, code) {
				if (continents[code]) {
					totalCount += continents[code].length;
					hasShortcut = true;
				} else {
					totalCount++;
				}
			});
			
			// Show badges - shortcuts first, then individual countries
			for (var i = 0; i < ruleCountries.length && displayCount < 4; i++) {
				var code = ruleCountries[i];
				if (code === 'worldwide') {
					var label = cookieadmin_geo_vars.lang['shortcut_' + code] || code;
					countryBadges += '<span class="cookieadmin-badge cookieadmin-primary" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' + escHtml(label) + '</span>';
					displayCount++;
				} else if (continents[code]) {
					var label = cookieadmin_geo_vars.lang['shortcut_' + code] || code;
					countryBadges += '<span class="cookieadmin-badge cookieadmin-primary" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' + escHtml(label) + ' (' + continents[code].length + ')</span>';
					displayCount++;
				} else {
					countryBadges += '<span class="cookieadmin-badge cookieadmin-info" style="padding:1px 8px; font-size:11px; margin-right:4px; margin-bottom:2px; display:inline-flex;">' + escHtml(code) + '</span>';
					displayCount++;
				}
			}
			
			if (ruleCountries.length > displayCount) {
				countryBadges += '<span class="cookieadmin-text-muted" style="font-size:11px;">+' + escHtml(ruleCountries.length - displayCount) + '</span>';
			}

			var scriptBlocking = rule.block_scripts || 'inherit';
			var contentBlocking = rule.content_blocking || 'inherit';
			var googleConsentMode = rule.google_consent_mode_v2 || 'inherit';
			var clarityConsent = rule.clarity_consent || 'inherit';
			
			// If the label is empty that means the rule type is none, so everything is off
			if(ruleLawLabel == ''){
				scriptBlocking = 'off';
				contentBlocking = 'off';
				googleConsentMode = 'off';
				clarityConsent = 'off';
			}

			html += '<tr data-rule-id="' + escAttr(ruleId) + '">';
			html += '<td><strong>' + escHtml(ruleName) + '</strong></td>';
			html += '<td>' + (ruleLawLabel ? escHtml(ruleLawLabel) : 'None') + '</td>';
			html += '<td>' + countryBadges + '</td>';
			html += '<td>' + renderBadgeHtml(scriptBlocking) + '</td>';
			html += '<td>' + renderBadgeHtml(contentBlocking) + '</td>';
			html += '<td>' + renderBadgeHtml(googleConsentMode) + '</td>';
			html += '<td>' + renderBadgeHtml(clarityConsent) + '</td>';
			html += '<td style="text-align:right;">';
			html += '<button type="button" class="cookieadmin-btn cookieadmin-btn-ghost cookieadmin-btn-sm cookieadmin-geo-table-edit" data-rule-id="' + escAttr(ruleId) + '" title="' + escAttr(cookieadmin_geo_vars.lang.edit) + '" style="margin-right:2px;"><span class="dashicons dashicons-edit"></span></button>';
			html += '<button type="button" class="cookieadmin-btn cookieadmin-btn-ghost cookieadmin-btn-sm cookieadmin-geo-table-delete" data-rule-id="' + escAttr(ruleId) + '" title="' + escAttr(cookieadmin_geo_vars.lang.delete) + '"><span class="dashicons dashicons-trash"></span></button>';
			html += '</td>';
			html += '</tr>';
		});

		html += '</tbody></table>';
		html += '</div></div>';
		$tableWrap.html(html);
	}

	function renderBadgeHtml(value) {
		var label = '';
		var cssClass = '';
		switch(value) {
			case 'inherit':
				label = cookieadmin_geo_vars.lang.inherit;
				cssClass = 'cookieadmin-info';
				break;
			case 'on':
				label = cookieadmin_geo_vars.lang.on;
				cssClass = 'cookieadmin-success';
				break;
			case 'off':
				label = cookieadmin_geo_vars.lang.off;
				cssClass = 'cookieadmin-danger';
				break;
			default:
				label = cookieadmin_geo_vars.lang.inherit;
				cssClass = 'cookieadmin-info';
		}
		return '<span class="cookieadmin-badge ' + escAttr(cssClass) + '" data-value="' + escAttr(value) + '" title="' + escAttr(label) + '" style="padding:1px 8px; font-size:11px;">' + escHtml(label) + '</span>';
	}

	function showCardForm(ruleData, rule_id) {
		ruleData = ruleData || {};
		editingRuleId = rule_id || null;

		$('#cookieadmin_geo_modal').addClass('active');

		$('#cookieadmin_geo_card_id').val(ruleData.id || '');
		$('#cookieadmin_geo_card_name').val(ruleData.name || '');
		$('#cookieadmin_geo_card_law').val(ruleData.law || '');

		var $tagsContainer = $('#cookieadmin_geo_card_tags').empty();
		var countries = window.cookieadmin_geo_vars.countries || {};
		var continents = window.cookieadmin_geo_vars.continents || {};
		var ruleCountries = ruleData.countries || [];
		
		$.each(ruleCountries, function(i, code) {
			if (code === 'worldwide') {
				var label = cookieadmin_geo_vars.lang['shortcut_' + code] || code;
				$tagsContainer.append(renderShortcutTagHtml(code, label, 0));
			} else if (continents[code]) {
				var label = cookieadmin_geo_vars.lang['shortcut_' + code] || code;
				$tagsContainer.append(renderShortcutTagHtml(code, label, continents[code].length));
			} else if (countries[code]) {
				$tagsContainer.append(renderCountryTagHtml(code, countries[code]));
			}
		});

		setToggleValue('block_scripts', ruleData.block_scripts || 'inherit');
		setToggleValue('content_blocking', ruleData.content_blocking || 'inherit');
		setToggleValue('google_consent_mode_v2', ruleData.google_consent_mode_v2 || 'inherit');
		setToggleValue('clarity_consent', ruleData.clarity_consent || 'inherit');
	}

	function hideCardForm() {
		$('#cookieadmin_geo_modal').removeClass('active');
		editingRuleId = null;
	}

	function saveCardForm(target) {
		var name = $('#cookieadmin_geo_card_name').val().trim();
		var law = $('#cookieadmin_geo_card_law').val();
		var ruleId = $('#cookieadmin_geo_card_id').val();
		var jEle = jQuery(target);

		if (!name) {
			showNotice('error', cookieadmin_geo_vars.lang.name_required);
			return;
		}

		var countries = [];
		$('#cookieadmin_geo_card_tags .cookieadmin-geo-country-tag').each(function() {
			var shortcut = $(this).data('shortcut');
			if (shortcut) {
				countries.push(shortcut);
			} else {
				countries.push($(this).data('code'));
			}
		});

		if (countries.length === 0) {
			showNotice('error', cookieadmin_geo_vars.lang.countries_required);
			return;
		}

		var scriptBlocking = getToggleValue('block_scripts');
		var contentBlocking = getToggleValue('content_blocking');
		var googleConsentMode = getToggleValue('google_consent_mode_v2');
		var clarityConsent = getToggleValue('clarity_consent');

		var rule = {
			name: name,
			law: law,
			countries: countries,
			block_scripts: scriptBlocking,
			content_blocking: contentBlocking,
			google_consent_mode_v2: googleConsentMode,
			clarity_consent: clarityConsent
		};

		if (editingRuleId) {
			rule['id'] = editingRuleId;
		}

		showNotice('success', cookieadmin_geo_vars.lang.rule_saved);

		// Auto-save to database
		saveRule(rule, jEle);
	}

	function editRule(ruleId) {
		var rule = null;
		$.each(rulesData, function(i, r) {
			if (i === ruleId) {
				rule = r;
				return false;
			}
		});
		
		if (rule) {
			showCardForm(rule, ruleId);
		}
	}

	function addPresetRule(preset) {
		var ruleData = {};

		if (preset === 'gdpr') {
			ruleData = {
				name: cookieadmin_geo_vars.lang.gdpr_preset_name,
				law: 'cookieadmin_gdpr',
				countries: ['europe'],
				block_scripts: 'on',
				content_blocking: 'on',
				google_consent_mode_v2: 'on',
				clarity_consent: 'inherit',
			};
		} else if (preset === 'us') {
			ruleData = {
				name: cookieadmin_geo_vars.lang.us_preset_name,
				law: 'cookieadmin_us',
				countries: ['US'],
				block_scripts: 'inherit',
				content_blocking: 'inherit',
				google_consent_mode_v2: 'inherit',
				clarity_consent: 'inherit',
			};
		} else if (preset === 'dpdp') {
			ruleData = {
				name: cookieadmin_geo_vars.lang.dpdp_preset_name,
				law: 'cookieadmin_dpdp',
				countries: ['IN'],
				block_scripts: 'on',
				content_blocking: 'on',
				google_consent_mode_v2: 'inherit',
				clarity_consent: 'inherit',
			};
		}

		showCardForm(ruleData);
	}

	function saveRule(rule, jEle) {
		hideNotice();

		var data = {
			action: 'cookieadmin_pro_ajax_handler',
			cookieadmin_act: 'save_geo_settings',
			cookieadmin_pro_security: cookieadmin_geo_vars.nonce,
		};

		if(typeof rule == 'string'){
			data['delete_id'] = rule

			let spinner = jQuery('#cookieadmin_geo_table_wrap').find('.cookieadmin-card-header .spinner');
			var td = jEle.closest('tr').find('td');

			spinner.addClass('is-active');
			spinner.css('display', 'inline-block');
			td.css('background', '#f8d7da');
			
		} else {
			data['rule'] = JSON.stringify(rule);
			
			var spinner = jEle.siblings('.spinner');
			spinner.addClass('is-active');
			spinner.show();
		}

		$.post(cookieadmin_geo_vars.ajax_url, data, function(response) {
			if (response.success) {
				showNotice('success', response.data && response.data.message ? response.data.message : cookieadmin_geo_vars.lang.saved);
				if(response.data.rules){
					rulesData = response.data.rules;
					renderTable();
					hideCardForm();
				}
			} else {
				showNotice('error', response.data && response.data.message ? response.data.message : cookieadmin_geo_vars.lang.save_error);
			}
			
			jQuery(spinner)?.removeClass('is-active');
			jQuery(spinner)?.hide();
			
			// Removing the color from td when after deletion completes
			if(td){
				jQuery(td)?.css('background', '#FFF');
			}
			
			
			updateEmptyState();
			
		}).fail(function() {
			showNotice('error', cookieadmin_geo_vars.lang.save_error);
			jQuery(spinner)?.removeClass('is-active');
			jQuery(spinner)?.hide();
		});
	}

	function onCountryAdd($select) {
		var code = $select.val();
		var location = '';
		if (!code) return;

		var countries = window.cookieadmin_geo_vars.countries || {};
		if(countries[code]){
			location = countries[code];
		}

		if(!location) return;

		var $tagsContainer = $('#cookieadmin_geo_card_tags');
		if ($tagsContainer.find('.cookieadmin-geo-country-tag[data-code="' + code + '"]').length) {
			$select.val('');
			return;
		}

		$tagsContainer.append(renderCountryTagHtml(code, location));
		$select.val('');
	}

	function addContinentCountries(continent) {
		var continents = window.cookieadmin_geo_vars.continents || {};
		var isWorldwide = (continent === 'worldwide');
		
		if (!isWorldwide && !continents[continent]) return;

		var $tagsContainer = $('#cookieadmin_geo_card_tags');

		// Check if this shortcut already exists
		if ($tagsContainer.find('.cookieadmin-geo-shortcut-tag[data-shortcut="' + continent + '"]').length) {
			showNotice('error', cookieadmin_geo_vars.lang.shortcut_exists);
			return;
		}

		if (isWorldwide) {
			// Worldwide: just add the tag, no country removal needed
			var label = cookieadmin_geo_vars.lang['shortcut_' + continent] || continent;
			$tagsContainer.append(renderShortcutTagHtml(continent, label, 0));
			showNotice('success', label + ' ' + cookieadmin_geo_vars.lang.shortcut_added);
			return;
		}

		// Remove any individual countries that are covered by this shortcut
		var codes = continents[continent];
		$.each(codes, function(i, code) {
			$tagsContainer.find('.cookieadmin-geo-country-tag[data-code="' + code + '"]').remove();
		});

		// Add shortcut tag
		var label = cookieadmin_geo_vars.lang['shortcut_' + continent] || continent;
		$tagsContainer.append(renderShortcutTagHtml(continent, label, codes.length));
		showNotice('success', label + ' ' + cookieadmin_geo_vars.lang.shortcut_added);
	}

	function renderShortcutTagHtml(shortcut, label, count) {
		var html = '<span class="cookieadmin-geo-country-tag cookieadmin-geo-shortcut-tag" data-shortcut="' + escAttr(shortcut) + '">';
		html += '<span class="cookieadmin-geo-country-tag-code">' + escHtml(label) + '</span>';
		if (shortcut !== 'worldwide') {
			html += '<span class="cookieadmin-geo-country-tag-name">(' + escHtml(count) + ' ' + (count === 1 ? cookieadmin_geo_vars.lang.country_singular : cookieadmin_geo_vars.lang.country_plural) + ')</span>';
		}
		html += '<button type="button" class="cookieadmin-geo-country-tag-remove" title="' + escAttr(cookieadmin_geo_vars.lang.remove_shortcut) + '">&times;</button>';
		html += '</span>';
		return html;
	}

	function renderCountryTagHtml(code, name) {
		var html = '<span class="cookieadmin-geo-country-tag" data-code="' + escAttr(code) + '">';
		html += '<span class="cookieadmin-geo-country-tag-code">' + escHtml(code) + '</span>';
		html += '<span class="cookieadmin-geo-country-tag-name">' + escHtml(name) + '</span>';
		html += '<button type="button" class="cookieadmin-geo-country-tag-remove" title="' + escAttr(cookieadmin_geo_vars.lang.remove_country) + '">&times;</button>';
		html += '</span>';
		return html;
	}

	function bindToggleEvents() {
		$(document).on('click', '.cookieadmin-geo-toggle-btn', function() {
			var $btn = $(this);
			var $toggle = $btn.closest('.cookieadmin-geo-toggle');
			var $buttons = $toggle.find('.cookieadmin-geo-toggle-btn');
			var $hiddenInput = $toggle.find('.cookieadmin-geo-toggle-value');

			$buttons.removeClass('cookieadmin-geo-toggle-active');
			$btn.addClass('cookieadmin-geo-toggle-active');
			if ($hiddenInput.length) {
				$hiddenInput.val($btn.data('value'));
			}
		});
	}

	function setToggleValue(field, value) {
		var $toggle = $('#cookieadmin_geo_card_' + field);
		if (!$toggle.length) return;

		var $buttons = $toggle.find('.cookieadmin-geo-toggle-btn');
		var $hiddenInput = $toggle.find('.cookieadmin-geo-toggle-value');

		$buttons.removeClass('cookieadmin-geo-toggle-active');
		$buttons.each(function() {
			if ($(this).data('value') === value) {
				$(this).addClass('cookieadmin-geo-toggle-active');
			}
		});

		if ($hiddenInput.length) {
			$hiddenInput.val(value);
		}
	}

	function getToggleValue(field) {
		var $toggle = $('#cookieadmin_geo_card_' + field);
		if (!$toggle.length) return 'inherit';
		var $hiddenInput = $toggle.find('.cookieadmin-geo-toggle-value');
		return $hiddenInput.length ? $hiddenInput.val() : 'inherit';
	}

	function updateEmptyState() {
		var $emptyState = $('#cookieadmin_geo_empty_state');
		if (!$emptyState.length) return;

		$emptyState.toggle(rulesData.length === 0);
	}

	function toggleGeoEnabled(isEnabled) {
		var data = {
			action: 'cookieadmin_pro_ajax_handler',
			cookieadmin_act: 'toggle_geo_enabled',
			cookieadmin_pro_security: cookieadmin_geo_vars.nonce,
			enabled: isEnabled ? '1' : '0'
		};

		$.post(cookieadmin_geo_vars.ajax_url, data, function(response) {
			if (!response.success) {
				showNotice('error', response.data && response.data.message ? response.data.message : cookieadmin_geo_vars.lang.toggle_error);
			}
		}).fail(function() {
			showNotice('error', cookieadmin_geo_vars.lang.toggle_error);
		});
	}

	function showNotice(type, message) {
		var $notice = $('#cookieadmin_geo_notice');
		if (!$notice.length) return;

		$notice.attr('class', 'cookieadmin-geo-notice cookieadmin-geo-notice-' + type);
		$notice.html('<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span> ' + escHtml(message));

		setTimeout(function() {
			hideNotice();
		}, 3000);
	}

	function hideNotice() {
		var $notice = $('#cookieadmin_geo_notice');
		if ($notice.length) {
			$notice.attr('class', 'cookieadmin-geo-notice').html('');
		}
	}

	function escHtml(str) {
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function escAttr(str) {
		return escHtml(String(str))
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}
})(jQuery);
