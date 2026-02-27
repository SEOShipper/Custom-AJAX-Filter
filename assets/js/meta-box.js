/**
 * Admin JS for product gallery picker and tabs repeater (WYSIWYG)
 *
 * Tab data is saved via standard form array fields (_product_tab_title[],
 * _product_tab_content[]) so that HTML content survives POST without
 * JSON encoding issues.  This script only manages TinyMCE lifecycle.
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

	$galleryGrid.on('click', '.apf-gallery-remove', function (e) {
		e.preventDefault();
		$(this).closest('.apf-gallery-item').remove();
		updateGalleryInput();
	});

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
	   Tabs Repeater with WYSIWYG (TinyMCE)

	   Each tab row carries:
	     - <input  name="_product_tab_title[]">
	     - <textarea name="_product_tab_content[]"> (wrapped by wp_editor)
	   PHP reads these arrays directly — no client-side JSON needed.
	   ====================================================================== */

	var $tabsList = $('#apf-tabs-list');

	if (!$tabsList.length) {
		return;
	}

	var tabCounter = parseInt($tabsList.data('next-index'), 10) || 0;

	var editorSettings = {
		tinymce: {
			wpautop: false,
			extended_valid_elements: 'table[width|border|cellspacing|cellpadding|class|style],tr[class|style],td[width|colspan|rowspan|class|style],th[width|colspan|rowspan|class|style|scope],thead,tbody,tfoot,img[src|alt|width|height|class|style],span[class|style],sup,sub'
		},
		quicktags: true,
		mediaButtons: true
	};

	function initEditor(editorId) {
		if (typeof wp !== 'undefined' && wp.editor) {
			wp.editor.initialize(editorId, editorSettings);
		}
	}

	function removeEditor(editorId) {
		if (typeof wp !== 'undefined' && wp.editor) {
			wp.editor.remove(editorId);
		}
	}

	function createTabRow() {
		var idx      = tabCounter++;
		var editorId = 'apf_tab_content_' + idx;

		var $row = $('<div>').addClass('apf-tab-row').attr({
			'data-index': idx,
			'data-editor-id': editorId
		});

		$row.append($('<span>').addClass('apf-tab-handle dashicons dashicons-menu'));
		$row.append($('<input>').attr({
			type: 'text',
			placeholder: 'Tab title',
			name: '_product_tab_title[]'
		}).addClass('apf-tab-title regular-text'));

		var $toggle = $('<button>').attr({ type: 'button', 'aria-label': 'Toggle content' }).addClass('apf-tab-toggle button-link');
		$toggle.append($('<span>').addClass('dashicons dashicons-arrow-up-alt2'));
		$row.append($toggle);

		var $remove = $('<button>').attr({ type: 'button', 'aria-label': 'Remove tab' }).addClass('apf-tab-remove button-link');
		$remove.append($('<span>').addClass('dashicons dashicons-trash'));
		$row.append($remove);

		var $contentWrap = $('<div>').addClass('apf-tab-content-wrap');
		var $textarea = $('<textarea>').attr({
			id: editorId,
			name: '_product_tab_content[]',
			rows: 10
		});
		$contentWrap.append($textarea);
		$row.append($contentWrap);

		return $row;
	}

	// Add tab
	$('#apf-tab-add').on('click', function (e) {
		e.preventDefault();
		var $row     = createTabRow();
		var editorId = $row.data('editor-id');
		$tabsList.append($row);
		initEditor(editorId);
		$row.find('.apf-tab-title').focus();
	});

	// Remove tab
	$tabsList.on('click', '.apf-tab-remove', function (e) {
		e.preventDefault();
		var $row     = $(this).closest('.apf-tab-row');
		var editorId = $row.data('editor-id');
		removeEditor(editorId);
		$row.remove();
	});

	// Toggle tab content visibility
	$tabsList.on('click', '.apf-tab-toggle', function (e) {
		e.preventDefault();
		var $row  = $(this).closest('.apf-tab-row');
		var $wrap = $row.find('.apf-tab-content-wrap');
		var $icon = $(this).find('.dashicons');

		$wrap.slideToggle(200, function () {
			if ($wrap.is(':visible')) {
				var editorId = $row.data('editor-id');
				if (typeof tinymce !== 'undefined') {
					var editor = tinymce.get(editorId);
					if (editor) {
						editor.fire('show');
					}
				}
			}
		});
		$icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
	});

	// Sortable: save/remove editors before DOM reorder, re-init after
	$tabsList.sortable({
		items: '.apf-tab-row',
		handle: '.apf-tab-handle',
		cursor: 'move',
		tolerance: 'pointer',
		start: function () {
			$tabsList.find('.apf-tab-row').each(function () {
				var editorId = $(this).data('editor-id');
				if (typeof tinymce !== 'undefined') {
					var editor = tinymce.get(editorId);
				if (editor && !editor.isHidden()) {
					editor.save();
				}
				}
				removeEditor(editorId);
			});
		},
		stop: function () {
			$tabsList.find('.apf-tab-row').each(function () {
				var editorId = $(this).data('editor-id');
				initEditor(editorId);
			});
		}
	});

	// Sync TinyMCE content to textareas before form submission
	$('#post').on('submit', function () {
		$tabsList.find('.apf-tab-row').each(function () {
			var editorId = $(this).data('editor-id');
			if (typeof tinymce !== 'undefined') {
				var editor = tinymce.get(editorId);
				if (editor && !editor.isHidden()) {
					editor.save();
				}
			}
		});
	});

})(jQuery);
