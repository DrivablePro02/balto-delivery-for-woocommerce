(function($) {
    'use strict';

    class balto_delivery_settings {
        constructor() {
            this.formSelector = '#balto-settings-form';
            this.init();
        }

        init() {
            $(document).ready(() => {
                this.bindEvents();
            });
        }

        bindEvents() {
            $(this.formSelector).on('submit', (e) => this.handleSubmit(e));
        }

        handleSubmit(event) {
            event.preventDefault();
            const form = $(event.currentTarget)[0];
        
            // Create a structured object to match PHP's expected format
            const formObject = {
                action: 'save_balto_settings',
                security: balto_delivery_settings_data.nonce,
                balto_delivery_settings: {}
            };
        
            // Process form fields
            const formFields = form.querySelectorAll('input, select, textarea');
            formFields.forEach(field => {
                // Skip submit button and nonce fields
                if (field.type === 'submit' || field.name.includes('nonce')) {
                    return;
                }
        
                // Extract section and key from field name
                // Updated regex to handle more cases
                const matches = field.name.match(/\[?balto_delivery_settings\]?\[?([^\[\]]+)\]?\[?([^\[\]]+)\]?/);
        
                if (matches) {
                    const [, section, key] = matches;
        
                    // Ensure section exists in formObject
                    if (!formObject.balto_delivery_settings[section]) {
                        formObject.balto_delivery_settings[section] = {};
                    }
        
                    if (field.type === 'checkbox') {
                        formObject.balto_delivery_settings[section][key] = field.checked ? field.value || 'yes' : 'no';
                    } else {
                        formObject.balto_delivery_settings[section][key] = field.value;
                    }
                } else {
                    console.warn('Field skipped (no match):', field.name);
                }
            });
        
            console.log('Form Data Sent:', formObject); // Debugging
        
            this.setLoadingState(true);
        
            // Make the AJAX call with properly structured data
            $.ajax({
                url: balto_delivery_settings_data.ajaxurl,
                type: 'POST',
                data: formObject,
                success: (response) => this.handleSuccess(response),
                error: (xhr, status, error) => this.handleError(error),
                complete: () => this.setLoadingState(false)
            });
        }
        

        handleSuccess(response) {
            if (response.success) {
                this.showNotice('success', response.data.message);
                // Optionally refresh the page or update form values
                // window.location.reload();
            } else {
                let errorMessage = response.data.message;
                if (response.data.code) {
                    errorMessage += ` (Error code: ${response.data.code})`;
                }
                this.showNotice('error', errorMessage);
                console.error('Settings save failed:', response);
            }
        }

        handleError(error) {
            this.showNotice('error', balto_delivery_settings_data.i18n.errorMessage);
            console.error('AJAX error:', error);
        }

        setLoadingState(isLoading) {
            const submitButton = $(this.formSelector).find('input[type="submit"]');
            submitButton.prop('disabled', isLoading);
            submitButton.val(isLoading ? 
                balto_delivery_settings_data.i18n.saving : 
                balto_delivery_settings_data.i18n.saveSettings
            );
        }

        showNotice(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                </div>
            `);

            $('.notice').remove();
            $(this.formSelector).before(notice);

            if (wp.hasOwnProperty('notices')) {
                wp.notices.removeDismissible();
                wp.notices.addDismissible();
            }

            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 5000);
        }
    }

    // Initialize the settings
    new balto_delivery_settings();
})(jQuery);