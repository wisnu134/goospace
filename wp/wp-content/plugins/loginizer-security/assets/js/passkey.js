jQuery(document).ready(function($){

	if(!loginizer_browser_supports_webauth()){

		var $rElm = $('.loginizer_passkey_settings');
		var $lElm = $('.loginizer_passkey_login_block');

		if($rElm.length > 0){
			$('#message').text(loginizer_pro_passkey.strings.not_supported || 'Passkeys require a secure HTTPS connection and a compatible browser.').show();
			$('#loginizer_register_passkey').prop('disabled', true);
		}
		if($lElm.length > 0){
			$lElm.remove();
		}

		return;
	}

	// =========================
	// PASSKEY REGISTRATION
	// =========================

	$('#loginizer_register_passkey').on('click', function(){

		if(!loginizer_browser_supports_webauth()){
			$('#message').text(loginizer_pro_passkey.strings.not_supported || 'Passkeys require a secure HTTPS connection and a compatible browser.').show();
			return;
		}

		$('.loginizer_passkey_loader_a').addClass('is-active').show();
		$('#message').hide();

		loginizer_passkey_ajax('loginizer_pro_passkey_register').then(function(resp){
			// console.log(resp.data);

			if(!resp.success){
				$('.loginizer_passkey_loader_a').removeClass('is-active').hide();
				$('#message').text(resp.data).show();
				return;
			}

			if(!resp.data || !resp.data.publicKey){
				$('.loginizer_passkey_loader_a').removeClass('is-active').hide();
				$('#message').text(loginizer_pro_passkey.strings.passkey_limit).show();
				return;
			}

			var passkeyName = resp.data.publicKey.passkeyName;
			var publicKey = resp.data.publicKey;

			// Convert base64Url encoded data into Array buffer
			publicKey.challenge = loginizer_base64url_to_array_buffer(publicKey.challenge);
			publicKey.user.id = loginizer_base64url_to_array_buffer(publicKey.user.id);
			
			// convert each credentialid onto the Array Buffer
			if(publicKey.excludeCredentials){
				publicKey.excludeCredentials = publicKey.excludeCredentials.map(function(c){
					c.id = loginizer_base64url_to_array_buffer(c.id);
					return c;
				});
			}

			$('.loginizer_passkey_loader_a').removeClass('is-active').hide();

			if(!navigator.credentials || !navigator.credentials.create){
				$('#message').text(loginizer_pro_passkey.strings.not_supported || 'Passkeys require a secure HTTPS connection and a compatible browser.').show();
				return;
			}

			navigator.credentials.create({ publicKey: publicKey }).then(function(credentials){

				// Convert ArrayBuffer into Base64Url-encoding and send back to php
				credentials.rawId = loginizer_array_buffer_to_base64url(credentials.rawId);
				credentials.response.attestationObject = loginizer_array_buffer_to_base64url(credentials.response.attestationObject);
				credentials.response.clientDataJSON = loginizer_array_buffer_to_base64url(credentials.response.clientDataJSON);
				if(passkeyName){
					loginizer_show_passkey_input(credentials);
				}else{
					// Register the passkey
					loginizer_verify_registration(credentials);
				}

			}).catch(function(err){
				if(err && err.name === 'InvalidStateError'){
					$('#message').text(loginizer_pro_passkey.strings.passkey_exist).show();
				}else if(err && err.name === 'NotAllowedError'){
					// User cancelled prompt, nothing to perform
				}else{
					$('#message').text(err.message).show();
				}
			});
		}).catch(function(err){
			$('.loginizer_passkey_loader_a').removeClass('is-active').hide();
			var errMsg = (err && err.responseJSON && err.responseJSON.data) ? err.responseJSON.data : (err && err.message ? err.message : 'Error during passkey registration');
			$('#message').text(errMsg).show();
		});
	});

	// =========================
	// PASSKEY LOGIN
	// =========================

	// Add Login with Passkey button at the end of the login form
	// This is only for the wordpress default login
	var $login_form = $("#loginform");
	var $passkey = $('.loginizer_passkey_login_block');

	if($passkey.length > 0 && $login_form.length > 0){
		$login_form.append($passkey);
	}

	// Verify passkey authentication
	$("#loginizer_passkey_login").on("click", function(){
		var $btn = $(this);
		var originalText = $btn.data('orig-text') || $btn.text();
		$btn.data('orig-text', originalText);
		$('.loginizer_passkey_login_error').hide().empty();

		if(!loginizer_browser_supports_webauth() || !navigator.credentials || !navigator.credentials.get){
			$('.loginizer_passkey_login_error').text(loginizer_pro_passkey.strings.not_supported || 'Passkeys require a secure HTTPS connection and a compatible browser.').show();
			return;
		}

		$btn.prop('disabled', true);

		var resetBtn = function(showError, errorMsg){
			$btn.prop('disabled', false).removeClass('is-redirecting').text(originalText);
			if(showError && errorMsg){
				$('.loginizer_passkey_login_error').text(errorMsg).show();
			}
		};

		var passkey_login = loginizer_passkey_ajax('loginizer_pro_passkey_authentication');
		passkey_login.then(function(resp){
			if(resp.success){

				if(!resp.data || !resp.data.public_key){
					$btn.text(loginizer_pro_passkey.strings.redirecting || 'Redirecting...').addClass('is-redirecting');
					if($('#login-message').length > 0){
						$('#login-message').text(loginizer_pro_passkey.strings.already_login);
					}
					window.location.reload();
					return;
				}

				var publicKey = resp.data.public_key.publicKey;
				var request_id = resp.data.request_id;
				publicKey.challenge = loginizer_base64url_to_array_buffer(publicKey.challenge);

				navigator.credentials.get({publicKey : publicKey}).then(function(credentials){

					// Convert ArrayBuffer into Base64Url-encoding and send back to php
					credentials.rawId = loginizer_array_buffer_to_base64url(credentials.rawId);
					credentials.response.authenticatorData = loginizer_array_buffer_to_base64url(credentials.response.authenticatorData);
					credentials.response.clientDataJSON = loginizer_array_buffer_to_base64url(credentials.response.clientDataJSON);
					credentials.response.signature = loginizer_array_buffer_to_base64url(credentials.response.signature);
					if(credentials.response.userHandle){
						credentials.response.userHandle = loginizer_array_buffer_to_base64url(credentials.response.userHandle);
					}

					var redirect_to = $('input[name="redirect_to"]').val() || '';
					var login_verify = loginizer_passkey_ajax('loginizer_pro_passkey_verify_authentication', {
						credentials : JSON.stringify({credentials}),
						request_id : request_id,
						redirect_to : redirect_to
					});
					login_verify.then(function(resp){
						if(resp.success && resp.data){
							$btn.text(loginizer_pro_passkey.strings.redirecting || 'Redirecting...').addClass('is-redirecting').prop('disabled', true);
							window.location.href = resp.data;
						}else{
							var errMsg = (resp && resp.data) ? resp.data : loginizer_pro_passkey.strings.auth_failed;
							resetBtn(true, errMsg);
						}
					}).catch(function(err){
						var errMsg = (err && err.responseJSON && err.responseJSON.data) ? err.responseJSON.data : loginizer_pro_passkey.strings.auth_failed;
						resetBtn(true, errMsg);
					});
				}).catch(function(err){
					if(err && err.name === 'NotAllowedError'){
						// User cancelled prompt
						resetBtn(false);
						return;
					}
					resetBtn(true, loginizer_pro_passkey.strings.auth_failed);
				});
			}else{
				var errMsg = (resp && resp.data) ? resp.data : loginizer_pro_passkey.strings.auth_failed;
				resetBtn(true, errMsg);
			}
		}).catch(function(err){
			var errMsg = (err && err.responseJSON && err.responseJSON.data) ? err.responseJSON.data : loginizer_pro_passkey.strings.auth_failed;
			resetBtn(true, errMsg);
		});
	});

	// Delete the passkeys
	$('.loginizer-delete-passkey').on('click', function(){
		return confirm(loginizer_pro_passkey.strings.delete_passkey);
	});

	// Delete the passkeys
	$('#message').on('click', function(){
		$(this).hide();
	});
});

function loginizer_show_passkey_input(credentials){

	jQuery('#loginizer_register_passkey').hide();
	jQuery('.loginizer-passkey-name-container').show();
	jQuery('#loginizer_passkey_name').focus();
	jQuery('.wp-list-table').addClass('loginizer_passkey_table_blur');

	function loginizer_submit_passkey_data(){
		var passkey_name = loginizer_get_passkey_name();
		var reg_ex = /^[a-zA-Z0-9_\-\s]+$/;
		if(!passkey_name || !reg_ex.test(passkey_name)){
			jQuery('#message').text(loginizer_pro_passkey.strings.empty_name).show();
			return loginizer_show_passkey_input(credentials);
		}
		jQuery('.loginizer_passkey_loader_b').addClass('is-active').show();
		loginizer_verify_registration(credentials, passkey_name);
	}

	jQuery('#loginizer_passkey_name').off('keypress').on('keypress', function(e){
		if(e.which === 13){
			loginizer_submit_passkey_data();
		}
	});

	jQuery('#loginizer_add_passkey_name').off('click').on('click', loginizer_submit_passkey_data);

	jQuery('#loginizer_cancel_passkey_name').off('click').on('click', function(){
		jQuery('.wp-list-table').removeClass('loginizer_passkey_table_blur');
		jQuery('#loginizer_register_passkey').show();
		jQuery('.loginizer-passkey-name-container').hide();
		jQuery('#loginizer_passkey_name').val('');
		jQuery('.loginizer_passkey_loader_b').removeClass('is-active').hide();
		jQuery('#message').hide();
	});
}

function loginizer_get_passkey_name(){

	var passkey_name = jQuery('#loginizer_passkey_name').val().trim();
	if(passkey_name == ''){
		return false;
	}

	jQuery('#loginizer_passkey_name').val('');
	return passkey_name;
}

function loginizer_verify_registration(credentials, passkeyName = ''){

	loginizer_passkey_ajax('loginizer_pro_passkey_verify_register', {
		credentials: JSON.stringify({credentials: credentials}),
		passkey_name: passkeyName
	}).then(function(resp){
		if(resp.success){
			location.reload();
		}else{
			jQuery('#message').text(resp.data).show();
			jQuery('#loginizer_cancel_passkey_name').click();
		}
	}).catch(function(err){
		jQuery('#message').text(err).show();
	});
}

function loginizer_passkey_ajax(action, extra_data = {}){

	const data = jQuery.extend({
		action: action,
		security: loginizer_pro_passkey.nonce
	},extra_data);

	return jQuery.ajax({
		url: loginizer_pro_passkey.url,
		method: 'POST',
		data: data
	});
}

function loginizer_base64url_to_array_buffer(base64url){
    // Remove any MIME encoding garbage
	base64url = base64url.replace(/^=\?BINARY\?B\?|\?=$/g, '');

    // Convert base64url → base64
    var padding = '='.repeat((4 - base64url.length % 4) % 4);
    var base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');

    // Decode
    var raw = atob(base64);

    // Convert to ArrayBuffer
    var buffer = new Uint8Array(raw.length);
    for(let i = 0; i < raw.length; i++){
        buffer[i] = raw.charCodeAt(i);
    }
    return buffer;
}

function loginizer_array_buffer_to_base64url(buffer){

	// Create a view for the arraybuffer
    var bytes = new Uint8Array(buffer);
    var binary = '';

	// crete binary code from bytes of ASCII code to char
	for(var i = 0; i < bytes.length; i++){
		binary += String.fromCharCode(bytes[i]);
	}

	// Encode
    var base64 = btoa(binary);
	// Convert into Base64Url-encoded string
    return base64.replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}

function loginizer_browser_supports_webauth(){
	return !!(window.isSecureContext && window.PublicKeyCredential && navigator.credentials && navigator.credentials.create && navigator.credentials.get);
}