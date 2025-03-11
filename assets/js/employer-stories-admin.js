/**
 * Employer Stories Admin JavaScript
 */
(function($) {
    'use strict';

    /**
     * Initialize the admin functionality
     */
    const EmployerStoriesAdmin = {
        /**
         * Initialize functions
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Initialize when document is ready
            $(document).ready(this.onDocumentReady);
        },

        /**
         * Document ready handler
         */
        onDocumentReady: function() {
            // Add help tooltips to ACF fields
            EmployerStoriesAdmin.addFieldTooltips();
            
            // Enhance the shortcode meta box
            EmployerStoriesAdmin.enhanceShortcodeBox();
            
            // Add copy functionality to shortcode examples
            EmployerStoriesAdmin.addCopyFunctionality();
        },

        /**
         * Add tooltips to ACF fields
         */
        addFieldTooltips: function() {
            // Only run on the employer story edit screen
            if (!$('body').hasClass('post-type-employer-story')) {
                return;
            }

            // Add tooltip to header image field
            $('.acf-field[data-name="header_image"] .acf-label label').append(
                '<span class="dashicons dashicons-info" style="margin-left: 5px; color: #0073aa;" title="This image appears at the top of the single story page. Use a large image (recommended: 1200x400px)."></span>'
            );

            // Add tooltip to employer stats field
            $('.acf-field[data-name="employer_stats"] .acf-label label').append(
                '<span class="dashicons dashicons-info" style="margin-left: 5px; color: #0073aa;" title="These stats appear in the four boxes below the header image."></span>'
            );

            // Initialize tooltips if WordPress has jQuery UI Tooltip
            if ($.fn.tooltip) {
                $('.dashicons-info').tooltip();
            }
        },

        /**
         * Enhance the shortcode meta box
         */
        enhanceShortcodeBox: function() {
            // Only run on the employer story edit screen
            if (!$('body').hasClass('post-type-employer-story')) {
                return;
            }

            // Add copy buttons to shortcode examples
            $('#employer-stories-shortcode code').each(function() {
                const $code = $(this);
                const $button = $('<button type="button" class="button button-small" style="margin-top: 5px;">Copy Shortcode</button>');
                
                $button.on('click', function() {
                    const tempTextarea = document.createElement('textarea');
                    tempTextarea.value = $code.text();
                    document.body.appendChild(tempTextarea);
                    tempTextarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempTextarea);
                    
                    // Show copied message
                    const $this = $(this);
                    const originalText = $this.text();
                    $this.text('Copied!').addClass('button-primary');
                    
                    setTimeout(function() {
                        $this.text(originalText).removeClass('button-primary');
                    }, 2000);
                });
                
                $code.after($button);
            });
        },

        /**
         * Add copy functionality to shortcode examples on help page
         */
        addCopyFunctionality: function() {
            // Only run on the help page
            if (!$('body').hasClass('employer-story_page_employer-stories-help')) {
                return;
            }

            // Add copy buttons to shortcode examples
            $('.es-shortcode-example').each(function() {
                const $example = $(this);
                const $button = $('<button type="button" class="button button-small" style="margin-top: 10px;">Copy Shortcode</button>');
                
                $button.on('click', function() {
                    const tempTextarea = document.createElement('textarea');
                    tempTextarea.value = $example.text().trim();
                    document.body.appendChild(tempTextarea);
                    tempTextarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempTextarea);
                    
                    // Show copied message
                    const $this = $(this);
                    const originalText = $this.text();
                    $this.text('Copied!').addClass('button-primary');
                    
                    setTimeout(function() {
                        $this.text(originalText).removeClass('button-primary');
                    }, 2000);
                });
                
                $example.after($button);
            });
        }
    };

    // Initialize everything
    EmployerStoriesAdmin.init();

})(jQuery);
