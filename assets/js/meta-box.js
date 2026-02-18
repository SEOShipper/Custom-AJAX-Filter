/**
 * Admin JS for product gallery picker and tabs repeater
 */
(function ($) {
	'use strict';

	/* ======================================================================
	   Gallery Media Picker
	   ====================================================================== */

	var $galleryGrid  = $('#apf-gallery-grid');
	var $galleryInput = $('#apf-gallery-ids');

	function updateGalleryInput() {
		var ids = [];
		$galleryGrid.find('.apf-gallery-item').each(function () {
			ids.push($(this).data('id'));
		});
		$galleryInput.val(ids.join(','));
	}

	// Add images button
	$('#apf-gallery-add').on('click', function (e) {
		e.preventDefault();

		var frame = wp.media({
			title: 'Select Gallery Images',
			button: { text: 'Add to Gallery' },
			multiple: true,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachments = frame.state().get('selection').toJSON();
			$.each(attachments, function (i, attachment) {
				var thumb = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				var $item = $('<div>').addClass('apf-gallery-item').attr('data-id', attachment.id);
				var $img  = $('<img>').attr('src', thumb).attr('alt', '');
				var $btn  = $('<button>').attr('type', 'button').addClass('apf-gallery-remove')
								.attr('aria-label', 'Remove image').html('&times;');
				$item.append($img).append($btn);
				$galleryGrid.append($item);
			});
			updateGalleryInput();
		});

		frame.open();
	});

	// Remove single image
	$galleryGrid.on('click', '.apf-gallery-remove', function (e) {
		e.preventDefault();
		$(this).closest('.apf-gallery-item').remove();
		updateGalleryInput();
	});

	// Sortable gallery
	if ($galleryGrid.length) {
		$galleryGrid.sortable({
			items: '.apf-gallery-item',
			cursor: 'move',
			tolerance: 'pointer',
			update: function () {
				updateGalleryInput();
			}
		});
	}

	/* ======================================================================
	   Tabs Repeater
	   ====================================================================== */

	var $tabsList  = $('#apf-tabs-list');
	var $tabsInput = $('#apf-tabs-json');

	function updateTabsInput() {
		var tabs = [];
		$tabsList.find('.apf-tab-row').each(function () {
			var title   = $(this).find('.apf-tab-title').val().trim();
			var content = $(this).find('.apf-tab-content').val().trim();
			if (title) {
				tabs.push({ title: title, content: content });
			}
		});
		$tabsInput.val(JSON.stringify(tabs));
	}

	function createTabRow(idx) {
		var $row = $('<div>').addClass('apf-tab-row').attr('data-index', idx);

		$row.append($('<span>').addClass('apf-tab-handle dashicons dashicons-menu'));
		$row.append($('<input>').attr({ type: 'text', placeholder: 'Tab title' }).addClass('apf-tab-title regular-text'));

		var $toggle = $('<button>').attr({ type: 'button', 'aria-label': 'Toggle content' }).addClass('apf-tab-toggle button-link');
		$toggle.append($('<span>').addClass('dashicons dashicons-arrow-down-alt2'));
		$row.append($toggle);

		var $remove = $('<button>').attr({ type: 'button', 'aria-label': 'Remove tab' }).addClass('apf-tab-remove button-link');
		$remove.append($('<span>').addClass('dashicons dashicons-trash'));
		$row.append($remove);

		var $contentWrap = $('<div>').addClass('apf-tab-content-wrap');
		$contentWrap.append($('<textarea>').addClass('apf-tab-content large-text').attr({ rows: 5, placeholder: 'Tab content (HTML allowed)' }));
		$row.append($contentWrap);

		return $row;
	}

	// Add tab
	$('#apf-tab-add').on('click', function (e) {
		e.preventDefault();
		var idx = $tabsList.find('.apf-tab-row').length;
		var $row = createTabRow(idx);
		$tabsList.append($row);
		$row.find('.apf-tab-title').focus();
		updateTabsInput();
	});

	// Remove tab
	$tabsList.on('click', '.apf-tab-remove', function (e) {
		e.preventDefault();
		$(this).closest('.apf-tab-row').remove();
		updateTabsInput();
	});

	// Toggle tab content visibility
	$tabsList.on('click', '.apf-tab-toggle', function (e) {
		e.preventDefault();
		var $wrap = $(this).closest('.apf-tab-row').find('.apf-tab-content-wrap');
		$wrap.slideToggle(150);
		var $icon = $(this).find('.dashicons');
		$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
	});

	// Update JSON on input change
	$tabsList.on('input change', '.apf-tab-title, .apf-tab-content', function () {
		updateTabsInput();
	});

	// Sortable tabs
	if ($tabsList.length) {
		$tabsList.sortable({
			items: '.apf-tab-row',
			handle: '.apf-tab-handle',
			cursor: 'move',
			tolerance: 'pointer',
			update: function () {
				updateTabsInput();
			}
		});
	}

})(jQuery);
