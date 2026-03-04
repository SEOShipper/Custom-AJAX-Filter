(function( $ ) {
	'use strict';

	var importData = null;
	var importKey  = null;

	var MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

	// =========================================================================
	// Export: scope toggle + select-all
	// =========================================================================

	$( 'input[name="export_scope"]' ).on( 'change', function() {
		var checklist = $( '#apf-product-checklist' );
		if ( 'selected' === $( this ).val() ) {
			checklist.slideDown( 200 );
		} else {
			checklist.slideUp( 200 );
		}
	});

	$( '#apf-check-all' ).on( 'change', function() {
		$( 'input[name="export_products[]"]' ).prop( 'checked', this.checked );
	});

	// Uncheck "select all" if any individual is unchecked
	$( document ).on( 'change', 'input[name="export_products[]"]', function() {
		if ( ! this.checked ) {
			$( '#apf-check-all' ).prop( 'checked', false );
		} else {
			var total   = $( 'input[name="export_products[]"]' ).length;
			var checked = $( 'input[name="export_products[]"]:checked' ).length;
			$( '#apf-check-all' ).prop( 'checked', total === checked );
		}
	});

	// Validate export form
	$( '#apf-export-form' ).on( 'submit', function( e ) {
		var scope = $( 'input[name="export_scope"]:checked' ).val();
		if ( 'selected' === scope ) {
			var selected = $( 'input[name="export_products[]"]:checked' ).length;
			if ( 0 === selected ) {
				e.preventDefault();
				alert( 'Please select at least one product to export.' );
			}
		}
	});

	// =========================================================================
	// Import: file selection + validation
	// =========================================================================

	$( '#apf-import-file' ).on( 'change', function() {
		var file = this.files[0];
		if ( ! file ) {
			return;
		}

		if ( ! file.name.match( /\.json$/i ) ) {
			alert( 'Please select a .json file.' );
			this.value = '';
			return;
		}

		if ( file.size > MAX_FILE_SIZE ) {
			alert( 'File is too large. Maximum allowed size is 10 MB.' );
			this.value = '';
			return;
		}

		var reader = new FileReader();
		reader.onload = function( e ) {
			try {
				var parsed = JSON.parse( e.target.result );
			} catch ( err ) {
				alert( 'Invalid JSON file: ' + err.message );
				return;
			}

			validateImport( parsed );
		};
		reader.readAsText( file );
	});

	function validateImport( jsonData ) {
		$.ajax({
			url:      apfImportExport.ajaxurl,
			method:   'POST',
			dataType: 'json',
			data: {
				action:    'apf_import_validate',
				nonce:     apfImportExport.nonce,
				json_data: JSON.stringify( jsonData )
			},
			success: function( response ) {
				if ( ! response.success ) {
					alert( 'Validation failed: ' + response.data.message );
					return;
				}

				importData = jsonData;
				importKey  = response.data.import_key;
				showSummary( response.data );
			},
			error: function() {
				alert( 'Server error during validation. Please try again.' );
			}
		});
	}

	function showSummary( summary ) {
		$( '#apf-summary-site' ).text( summary.site_url );
		$( '#apf-summary-date' ).text( summary.exported_at );
		$( '#apf-summary-products' ).text( summary.product_count );
		$( '#apf-summary-terms' ).text( summary.term_count );
		$( '#apf-summary-settings' ).text( summary.has_settings ? 'Yes' : 'No' );

		// Disable settings checkbox if no settings in file
		$( '#apf-import-settings' ).prop( 'disabled', ! summary.has_settings );
		if ( ! summary.has_settings ) {
			$( '#apf-import-settings' ).prop( 'checked', false );
		}

		$( '#apf-import-upload' ).hide();
		$( '#apf-import-summary' ).show();
	}

	// =========================================================================
	// Import: cancel / reset
	// =========================================================================

	$( '#apf-import-cancel' ).on( 'click', function() {
		resetImport();
	});

	$( '#apf-import-reset' ).on( 'click', function() {
		resetImport();
	});

	function resetImport() {
		importData = null;
		importKey  = null;
		$( '#apf-import-file' ).val( '' );
		$( '#apf-import-summary' ).hide();
		$( '#apf-import-progress' ).hide();
		$( '#apf-import-results' ).hide();
		$( '#apf-import-upload' ).show();
	}

	// =========================================================================
	// Import: run
	// =========================================================================

	$( '#apf-import-start' ).on( 'click', function() {
		if ( ! importKey ) {
			return;
		}

		$( '#apf-import-summary' ).hide();
		$( '#apf-import-progress' ).show();

		var doTaxonomies = $( '#apf-import-taxonomies' ).is( ':checked' );
		var doSettings   = $( '#apf-import-settings' ).is( ':checked' );
		var dupMode      = $( 'input[name="duplicate_mode"]:checked' ).val();

		var totals = {
			created: 0,
			updated: 0,
			skipped: 0,
			terms:   0,
			errors:  []
		};

		var productCount = importData && importData.data && importData.data.products ? importData.data.products.length : 0;
		var steps        = [];

		// Calculate total steps for progress
		if ( doTaxonomies ) {
			steps.push( 'taxonomies' );
		}
		if ( doSettings ) {
			steps.push( 'settings' );
		}

		var batches = Math.ceil( productCount / apfImportExport.batchSize );
		for ( var i = 0; i < batches; i++ ) {
			steps.push( 'batch_' + i );
		}

		var totalSteps  = steps.length;
		var currentStep = 0;

		function updateProgress( label ) {
			var pct = totalSteps > 0 ? Math.round( ( currentStep / totalSteps ) * 100 ) : 0;
			$( '#apf-progress-fill' ).css( 'width', pct + '%' );
			$( '#apf-progress-text' ).text( label + ' (' + pct + '%)' );
		}

		function runNext() {
			if ( 0 === steps.length ) {
				showResults( totals );
				return;
			}

			var step = steps.shift();

			if ( 'taxonomies' === step ) {
				updateProgress( 'Importing taxonomy terms...' );
				$.ajax({
					url:      apfImportExport.ajaxurl,
					method:   'POST',
					dataType: 'json',
					data: {
						action:     'apf_import_taxonomies',
						nonce:      apfImportExport.nonce,
						import_key: importKey
					},
					success: function( response ) {
						currentStep++;
						if ( response.success ) {
							totals.terms = response.data.imported;
						}
						runNext();
					},
					error: function() {
						currentStep++;
						totals.errors.push( 'Taxonomy import failed (server error).' );
						runNext();
					}
				});
			} else if ( 'settings' === step ) {
				updateProgress( 'Importing settings...' );
				$.ajax({
					url:      apfImportExport.ajaxurl,
					method:   'POST',
					dataType: 'json',
					data: {
						action:     'apf_import_settings',
						nonce:      apfImportExport.nonce,
						import_key: importKey
					},
					success: function( response ) {
						currentStep++;
						runNext();
					},
					error: function() {
						currentStep++;
						totals.errors.push( 'Settings import failed (server error).' );
						runNext();
					}
				});
			} else if ( step.indexOf( 'batch_' ) === 0 ) {
				var batchIndex = parseInt( step.replace( 'batch_', '' ), 10 );
				var offset     = batchIndex * apfImportExport.batchSize;

				updateProgress( 'Importing products ' + ( offset + 1 ) + ' - ' + Math.min( offset + apfImportExport.batchSize, productCount ) + '...' );

				$.ajax({
					url:      apfImportExport.ajaxurl,
					method:   'POST',
					dataType: 'json',
					data: {
						action:         'apf_import_batch',
						nonce:          apfImportExport.nonce,
						import_key:     importKey,
						offset:         offset,
						duplicate_mode: dupMode
					},
					success: function( response ) {
						currentStep++;
						if ( response.success ) {
							totals.created += response.data.created;
							totals.updated += response.data.updated;
							totals.skipped += response.data.skipped;
							if ( response.data.errors && response.data.errors.length ) {
								totals.errors = totals.errors.concat( response.data.errors );
							}
						}
						runNext();
					},
					error: function() {
						currentStep++;
						totals.errors.push( 'Batch import failed at offset ' + offset + ' (server error).' );
						runNext();
					}
				});
			} else {
				currentStep++;
				runNext();
			}
		}

		runNext();
	});

	function showResults( totals ) {
		$( '#apf-progress-fill' ).css( 'width', '100%' );
		$( '#apf-progress-text' ).text( 'Complete (100%)' );

		$( '#apf-result-created' ).text( totals.created );
		$( '#apf-result-updated' ).text( totals.updated );
		$( '#apf-result-skipped' ).text( totals.skipped );
		$( '#apf-result-terms' ).text( totals.terms );
		$( '#apf-result-errors' ).text( totals.errors.length );

		if ( totals.errors.length > 0 ) {
			var $list = $( '#apf-error-messages' ).empty();
			$.each( totals.errors, function( _, msg ) {
				$list.append( $( '<li>' ).text( msg ) );
			});
			$( '#apf-result-error-list' ).show();
		} else {
			$( '#apf-result-error-list' ).hide();
		}

		$( '#apf-import-progress' ).hide();
		$( '#apf-import-results' ).show();
	}

})( jQuery );
