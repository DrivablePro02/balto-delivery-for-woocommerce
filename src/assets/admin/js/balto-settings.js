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
            const formData = new FormData();
            
            // Add all form fields
            const formFields = form.querySelectorAll('input, select, textarea');
            formFields.forEach(field => {
                if (field.type === 'checkbox') {
                    formData.append(field.name, field.checked ? field.value : '');
                } else if (field.type === 'radio') {
                    if (field.checked) {
                        formData.append(field.name, field.value);
                    }
                } else {
                    formData.append(field.name, field.value);
                }
            });

            // Add required action and nonce
            formData.append('action', 'save_balto_settings');
            formData.append('nonce', balto_delivery_settings_data.nonce);

            // Show loading state
            this.setLoadingState(true);

            // Make the AJAX call
            $.ajax({
                url: balto_delivery_settings_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => this.handleSuccess(response),
                error: (xhr, status, error) => this.handleError(error),
                complete: () => this.setLoadingState(false)
            });
        }

        handleSuccess(response) {
            if (response.success) {
                this.showNotice('success', response.data.message);
            } else {
                this.showNotice('error', response.data.message);
            }
        }

        handleError(error) {
            this.showNotice('error', balto_delivery_settings_data.i18n.errorMessage);
        }

        setLoadingState(isLoading) {
            const submitButton = $(this.formSelector).find('input[type="submit"]');
            if (isLoading) {
                submitButton.prop('disabled', true);
                submitButton.val(balto_delivery_settings_data.i18n.saving);
            } else {
                submitButton.prop('disabled', false);
                submitButton.val(balto_delivery_settings_data.i18n.saveSettings);
            }
        }

        showNotice(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);
            
            // Remove existing notices
            $('.notice').remove();
            
            // Add new notice
            $(this.formSelector).before(notice);
            
            // Make the notice dismissible
            if (wp.hasOwnProperty('notices')) {
                wp.notices.removeDismissible();
                wp.notices.addDismissible();
            }
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 5000);
        }
    }

    // Initialize the settings
    new balto_delivery_settings();

})(jQuery);