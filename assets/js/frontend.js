/**
 * ShopChat Frontend JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Chat Widget Popup Toggle
		const $widgetBtn = $('#shopchat-widget-button');
		const $popupBox  = $('#shopchat-popup-box');
		const $closeBtn  = $('#shopchat-popup-close');

		if ($widgetBtn.length) {
			$widgetBtn.on('click', function(e) {
				e.preventDefault();
				$popupBox.fadeToggle(200);
			});

			$closeBtn.on('click', function(e) {
				e.preventDefault();
				$popupBox.fadeOut(150);
			});

			// Close on outside click
			$(document).on('click', function(e) {
				if (!$(e.target).closest('#shopchat-widget-root').length) {
					$popupBox.fadeOut(150);
				}
			});
		}

		// 2. Checkout Abandoned Cart Tracker
		let captureTimer = null;

		function getFieldValue(selector) {
			const $elements = $(selector);
			for (let i = 0; i < $elements.length; i++) {
				const val = $($elements[i]).val();
				if (val && typeof val === 'string' && val.trim().length > 0) {
					return val.trim();
				}
			}
			return '';
		}

		function triggerCartCapture() {
			if (typeof shopchat_vars === 'undefined') {
				return;
			}

			// Comprehensive field discovery supporting both Classic WooCommerce and Gutenberg Blocks
			const phone = getFieldValue('#billing_phone, input[name="billing_phone"], input[type="tel"], #phone, #billing-phone, #shipping-phone, [autocomplete="tel"]');
			const email = getFieldValue('#billing_email, input[name="billing_email"], input[type="email"], #email, #billing-email, [autocomplete="email"]');
			const firstName = getFieldValue('#billing_first_name, #billing-first-name, input[name="billing_first_name"], [autocomplete="given-name"]');
			const lastName = getFieldValue('#billing_last_name, #billing-last-name, input[name="billing_last_name"], [autocomplete="family-name"]');
			const fullName = (firstName + ' ' + lastName).trim();

			// Only capture if phone or email is present
			if (!phone && !email) {
				return;
			}

			$.ajax({
				url: shopchat_vars.ajax_url,
				type: 'POST',
				data: {
					action: 'shopchat_capture_cart',
					nonce: shopchat_vars.nonce,
					phone: phone,
					email: email,
					name: fullName
				},
				success: function(response) {
					// Successfully recorded session in database
				}
			});
		}

		// Listen to all relevant inputs in both Classic and Blocks checkout
		const checkoutSelector = '#billing_phone, #billing_email, #billing_first_name, #billing_last_name, input[type="tel"], input[type="email"], #phone, #email, #billing-phone, #shipping-phone, #billing-email, [autocomplete="tel"], [autocomplete="email"]';

		$('body').on('input change blur keyup', checkoutSelector, function() {
			clearTimeout(captureTimer);
			captureTimer = setTimeout(triggerCartCapture, 600);
		});

		// Auto-check on page load after 1.5s in case fields are pre-filled or autofilled
		setTimeout(triggerCartCapture, 1500);
	});

})(jQuery);
