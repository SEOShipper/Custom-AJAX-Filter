/**
 * AJAX Product Filter JavaScript
 */

(function($) {
    'use strict';

    // Main filter controller
    var APFFilter = {
        // State
        state: {
            filters: {},
            search: '',
            sort: 'featured'
        },

        // DOM elements
        elements: {
            sidebar: null,
            grid: null,
            gridWrapper: null,
            searchInput: null,
            sortSelect: null,
            checkboxes: null,
            clearBtn: null,
            countDisplay: null,
            mobileToggle: null,
            overlay: null,
            panelClose: null
        },

        // Debounce timers
        debounceTimer: null,
        searchDebounceTimer: null,

        /**
         * Initialize the filter
         */
        init: function() {
            this.cacheElements();

            if (!this.elements.sidebar.length && !this.elements.gridWrapper.length) {
                return;
            }

            this.bindEvents();
            this.parseURLParams();
            this.updateCounts();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements.sidebar = $('.apf-filter-sidebar');
            this.elements.gridWrapper = $('.apf-product-grid-wrapper');
            this.elements.grid = this.elements.gridWrapper.find('.apf-product-grid');
            this.elements.searchInput = this.elements.sidebar.find('.apf-search-input');
            this.elements.sortSelect = this.elements.gridWrapper.find('.apf-sort-select');
            this.elements.checkboxes = this.elements.sidebar.find('.apf-checkbox');
            this.elements.clearBtn = this.elements.sidebar.find('.apf-clear-all');
            this.elements.countDisplay = this.elements.gridWrapper.find('.apf-count-number');
            this.elements.mobileToggle = this.elements.sidebar.find('.apf-mobile-toggle');
            this.elements.overlay = this.elements.sidebar.find('.apf-overlay');
            this.elements.panelClose = this.elements.sidebar.find('.apf-panel-close');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Checkbox change
            this.elements.checkboxes.on('change', function() {
                self.handleFilterChange();
            });

            // Search input
            this.elements.searchInput.on('input', function() {
                self.handleSearchInput($(this).val());
            });

            // Sort change
            this.elements.sortSelect.on('change', function() {
                self.state.sort = $(this).val();
                self.triggerFilter();
            });

            // Clear all
            this.elements.clearBtn.on('click', function() {
                self.clearAllFilters();
            });

            // Clear link in no results
            $(document).on('click', '.apf-clear-filters-link', function(e) {
                e.preventDefault();
                self.clearAllFilters();
            });

            // Collapsible sections
            this.elements.sidebar.on('click', '.apf-filter-header', function() {
                self.toggleSection($(this));
            });

            // Mobile drawer
            this.elements.mobileToggle.on('click', function() {
                self.openDrawer();
            });

            this.elements.overlay.on('click', function() {
                self.closeDrawer();
            });

            this.elements.panelClose.on('click', function() {
                self.closeDrawer();
            });

            // Handle browser back/forward
            $(window).on('popstate', function() {
                self.parseURLParams();
                self.applyStateToUI();
                self.triggerFilter(false); // Don't update URL again
            });
        },

        /**
         * Handle checkbox filter change
         */
        handleFilterChange: function() {
            var self = this;

            // Debounce filter changes
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(function() {
                self.collectFilters();
                self.triggerFilter();
            }, 150);
        },

        /**
         * Handle search input
         */
        handleSearchInput: function(value) {
            var self = this;

            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(function() {
                self.state.search = value.trim();
                self.triggerFilter();
            }, 300);
        },

        /**
         * Collect filter values from checkboxes
         */
        collectFilters: function() {
            var filters = {};

            this.elements.checkboxes.filter(':checked').each(function() {
                var $checkbox = $(this);
                var taxonomy = $checkbox.attr('name').replace('[]', '');
                var value = $checkbox.val();

                if (!filters[taxonomy]) {
                    filters[taxonomy] = [];
                }
                filters[taxonomy].push(value);
            });

            this.state.filters = filters;
        },

        /**
         * Trigger AJAX filter request
         */
        triggerFilter: function(updateURL) {
            var self = this;

            if (typeof updateURL === 'undefined') {
                updateURL = true;
            }

            // Update URL
            if (updateURL) {
                this.updateURL();
            }

            // Show loading
            this.elements.gridWrapper.addClass('apf-loading');

            // Build request data
            var data = {
                action: 'apf_filter_products',
                nonce: apfAjax.nonce,
                search: this.state.search,
                sort: this.state.sort,
                quote_url: this.elements.gridWrapper.data('quote-url') || '/contact/',
                product_type: this.elements.gridWrapper.data('product-type') || '',
                product_application: this.elements.gridWrapper.data('product-application') || '',
                limit: this.elements.gridWrapper.data('limit') || '',
                show_description: this.elements.gridWrapper.data('show-description') || ''
            };

            // Add filter taxonomies
            $.each(this.state.filters, function(taxonomy, terms) {
                data[taxonomy] = terms;
            });

            // AJAX request
            $.ajax({
                url: apfAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.updateGrid(response.data.html);
                        self.updateProductCount(response.data.count);
                        self.updateTermCounts(response.data.counts);
                    }
                },
                error: function() {
                    console.error('APF: Filter request failed');
                },
                complete: function() {
                    self.elements.gridWrapper.removeClass('apf-loading');
                }
            });
        },

        /**
         * Update grid HTML
         */
        updateGrid: function(html) {
            this.elements.grid.html(html);
        },

        /**
         * Update product count display
         */
        updateProductCount: function(count) {
            this.elements.countDisplay.text(count);
        },

        /**
         * Update term counts in sidebar
         */
        updateTermCounts: function(counts) {
            if (!counts) return;

            $.each(counts, function(taxonomy, termCounts) {
                $.each(termCounts, function(termSlug, count) {
                    var $countSpan = $('.apf-filter-group[data-taxonomy="' + taxonomy + '"]')
                        .find('.apf-term-count[data-term="' + termSlug + '"]');
                    $countSpan.text('(' + count + ')');
                });
            });
        },

        /**
         * Get initial counts
         */
        updateCounts: function() {
            var self = this;

            // Build request with current filters
            var data = {
                action: 'apf_filter_products',
                nonce: apfAjax.nonce,
                search: this.state.search,
                sort: this.state.sort,
                quote_url: this.elements.gridWrapper.data('quote-url') || '/contact/',
                product_type: this.elements.gridWrapper.data('product-type') || '',
                product_application: this.elements.gridWrapper.data('product-application') || '',
                limit: this.elements.gridWrapper.data('limit') || '',
                show_description: this.elements.gridWrapper.data('show-description') || ''
            };

            $.each(this.state.filters, function(taxonomy, terms) {
                data[taxonomy] = terms;
            });

            $.ajax({
                url: apfAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success && response.data.counts) {
                        self.updateTermCounts(response.data.counts);
                    }
                }
            });
        },

        /**
         * Clear all filters
         */
        clearAllFilters: function() {
            // Reset state
            this.state.filters = {};
            this.state.search = '';
            this.state.sort = 'featured';

            // Reset UI
            this.elements.checkboxes.prop('checked', false);
            this.elements.searchInput.val('');
            this.elements.sortSelect.val('featured');

            // Trigger filter
            this.triggerFilter();
        },

        /**
         * Toggle collapsible section
         */
        toggleSection: function($header) {
            var $group = $header.closest('.apf-filter-group');
            var isExpanded = $header.attr('aria-expanded') === 'true';

            $header.attr('aria-expanded', !isExpanded);
            $group.toggleClass('apf-collapsed');
        },

        /**
         * Open mobile drawer
         */
        openDrawer: function() {
            this.elements.sidebar.addClass('apf-drawer-open');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close mobile drawer
         */
        closeDrawer: function() {
            this.elements.sidebar.removeClass('apf-drawer-open');
            $('body').css('overflow', '');
        },

        /**
         * Update URL with current filter state
         */
        updateURL: function() {
            var params = new URLSearchParams();

            // Add filters
            $.each(this.state.filters, function(taxonomy, terms) {
                if (terms.length > 0) {
                    // Clean taxonomy name (remove product_ prefix for cleaner URLs)
                    var paramName = taxonomy.replace('product_', '');
                    params.set(paramName, terms.join(','));
                }
            });

            // Add search
            if (this.state.search) {
                params.set('s', this.state.search);
            }

            // Add sort (only if not default)
            if (this.state.sort && this.state.sort !== 'featured') {
                params.set('sort', this.state.sort);
            }

            // Build URL
            var url = window.location.pathname;
            var queryString = params.toString();
            if (queryString) {
                url += '?' + queryString;
            }

            // Update browser history
            window.history.pushState(this.state, '', url);
        },

        /**
         * Parse URL parameters on load
         */
        parseURLParams: function() {
            var params = new URLSearchParams(window.location.search);
            var self = this;

            // Reset state
            this.state.filters = {};
            this.state.search = '';
            this.state.sort = 'featured';

            // Parse params
            params.forEach(function(value, key) {
                if (key === 's') {
                    self.state.search = value;
                } else if (key === 'sort') {
                    self.state.sort = value;
                } else {
                    // Assume it's a filter taxonomy
                    var taxonomy = 'product_' + key;
                    self.state.filters[taxonomy] = value.split(',');
                }
            });

            // Apply to UI
            this.applyStateToUI();

            // Trigger filter if we have any params
            if (params.toString()) {
                this.triggerFilter(false);
            }
        },

        /**
         * Apply current state to UI elements
         */
        applyStateToUI: function() {
            var self = this;

            // Reset all checkboxes
            this.elements.checkboxes.prop('checked', false);

            // Set checked checkboxes based on state
            $.each(this.state.filters, function(taxonomy, terms) {
                $.each(terms, function(i, term) {
                    self.elements.checkboxes
                        .filter('[name="' + taxonomy + '[]"][value="' + term + '"]')
                        .prop('checked', true);
                });
            });

            // Set search
            this.elements.searchInput.val(this.state.search);

            // Set sort
            this.elements.sortSelect.val(this.state.sort);
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        APFFilter.init();
    });

})(jQuery);
