(function($) {
    'use strict';

    class BaltoSettings {
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
            formData.append('nonce', baltoSettings.nonce);

            // Debug: Log form data
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // Show loading state
            this.setLoadingState(true);

            // Make the AJAX call
            $.ajax({
                url: baltoSettings.ajaxurl,
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
            console.error('Error:', error);
            this.showNotice('error', baltoSettings.i18n.errorMessage);
        }

        setLoadingState(isLoading) {
            const submitButton = $(this.formSelector).find('input[type="submit"]');
            if (isLoading) {
                submitButton.prop('disabled', true);
                submitButton.val(baltoSettings.i18n.saving);
            } else {
                submitButton.prop('disabled', false);
                submitButton.val(baltoSettings.i18n.saveSettings);
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
    new BaltoSettings();

})(jQuery);