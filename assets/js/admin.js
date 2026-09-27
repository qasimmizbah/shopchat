/**
 * ShopChat Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Mode selection toggle
		$('input[name="general_settings[messaging_mode]"]').on('change', function() {
			$('.shopchat-mode-option').removeClass('is-selected');
			$(this).closest('.shopchat-mode-option').addClass('is-selected');

			if ($(this).val() === 'meta_cloud') {
				$('#shopchat-meta-credentials').slideDown(200);
			} else {
				$('#shopchat-meta-credentials').slideUp(200);
			}
		});

		// 2. Template Editor & Live Phone Simulator
		const $previewText = $('#shopchat-live-preview-text');
		let activeEvent = $('.shopchat-event-pill.is-selected').data('event') || 'processing';

		// Sample mockup data for replacing merge tags
		const sampleReplacements = {
			'{customer_name}': 'Rahul Sharma',
			'{order_id}': '1042',
			'{order_total}': '$89.00',
			'{order_status}': 'Processing',
			'{items_list}': '• Wireless Earbuds (x1)\n• Silicone Case (x1)',
			'{order_items}': '• Wireless Earbuds (x1)\n• Silicone Case (x1)',
			'{order_url}': 'https://mystore.test/order/1042',
			'{recovery_url}': 'https://mystore.test/checkout?recover=x9k2p',
			'{review_url}': 'https://mystore.test/product/earbuds#reviews',
			'{site_title}': $('.shopchat-wa-name').text().trim() || 'My Store'
		};

		function updatePhonePreview() {
			const $activeTextarea = $('#template-' + activeEvent);
			if (!$activeTextarea.length || !$previewText.length) {
				return;
			}

			let rawText = $activeTextarea.val() || '';

			// Replace tags with sample data
			$.each(sampleReplacements, function(tag, sampleVal) {
				rawText = rawText.split(tag).join(sampleVal);
			});

			// WhatsApp text format simulation (*bold*, _italic_)
			let formatted = rawText
				.replace(/\*(.*?)\*/g, '<strong>$1</strong>')
				.replace(/_(.*?)_/g, '<em>$1</em>');

			$previewText.html(formatted);
		}

		// Initial preview render
		updatePhonePreview();

		// Update on typing
		$('body').on('input keyup', '.shopchat-template-textarea', function() {
			updatePhonePreview();
		});

		// Event Pill Switcher
		$('.shopchat-event-pill').on('click', function(e) {
			e.preventDefault();
			$('.shopchat-event-pill').removeClass('is-selected');
			$(this).addClass('is-selected');

			activeEvent = $(this).data('event');
			$('.shopchat-event-block').hide();
			$('#event-block-' + activeEvent).fadeIn(150);

			updatePhonePreview();
		});

		// Tag Chip Click to Insert into Active Textarea
		$('.shopchat-tag-chip').on('click', function(e) {
			e.preventDefault();
			const tag = $(this).data('tag');
			const $textarea = $('#template-' + activeEvent);

			if (!$textarea.length) {
				return;
			}

			const el = $textarea[0];
			const start = el.selectionStart;
			const end = el.selectionEnd;
			const text = el.value;

			el.value = text.substring(0, start) + tag + text.substring(end);
			el.selectionStart = el.selectionEnd = start + tag.length;
			$textarea.focus();

			updatePhonePreview();
		});

		// 3. Meta API Connection Live Test
		$('#shopchat_test_meta_btn').on('click', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const $status = $('#shopchat_test_status');
			const phone = $('#shopchat_test_phone').val().trim();

			if (!phone) {
				$status.css('color', '#dc2626').text('Please enter a recipient phone number.');
				return;
			}

			$btn.prop('disabled', true).text('Sending...');
			$status.css('color', '#64748b').text('Connecting to Meta Cloud API...');

			$.ajax({
				url: window.shopchat_admin_vars.ajax_url,
				type: 'POST',
				data: {
					action: 'shopchat_test_meta_api',
					nonce: window.shopchat_admin_vars.nonce,
					phone: phone
				},
				success: function(res) {
					$btn.prop('disabled', false).text('Send Test WhatsApp');
					if (res && res.success) {
						$status.css('color', '#16a34a').text('Message sent! ID: ' + (res.data.message_id || 'OK'));
					} else {
						const err = (res && res.data && res.data.message) ? res.data.message : 'Error sending message.';
						$status.css('color', '#dc2626').text('Failed: ' + err);
					}
				},
				error: function() {
					$btn.prop('disabled', false).text('Send Test WhatsApp');
					$status.css('color', '#dc2626').text('Server error occurred while testing API.');
				}
			});
		});

		// 4. Send Cart Recovery via Meta API from Table
		$('body').on('click', '.shopchat-send-api-btn', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const cartId = $btn.data('cart-id');

			$btn.prop('disabled', true).text('Sending...');

			$.ajax({
				url: window.shopchat_admin_vars.ajax_url,
				type: 'POST',
				data: {
					action: 'shopchat_send_cart_recovery',
					nonce: window.shopchat_admin_vars.nonce,
					cart_id: cartId
				},
				success: function(res) {
					if (res && res.success) {
						$btn.text('Sent ✓').css({ background: '#16a34a', borderColor: '#15803d', color: '#fff' });
					} else {
						alert((res && res.data && res.data.message) ? res.data.message : 'Error sending message.');
						$btn.prop('disabled', false).text('Send API');
					}
				},
				error: function() {
					alert('Server error while sending recovery message.');
					$btn.prop('disabled', false).text('Send API');
				}
			});
		});
	});

})(jQuery);
