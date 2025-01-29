/**
 * Class for handling the settings of the Balto Delivery plugin
 */
class balto_delivery_settings {
    /**
     * Constructor
     */
    constructor()
    {
        /**
         * The selector for the settings form
         * @type {string}
         */
        this.formSelector = '#balto-settings-form';

        /**
         * Initialize the class
         */
        this.init();
    }

    /**
     * Initialize the class
     */
    init()
    {
        /**
         * Bind events when the document is ready
         */
        $(document).ready(
            () => {
                this.bindEvents();
            }
        );
    }

    /**
     * Bind events
     */
    bindEvents()
    {
        /**
         * Bind the form submit event
         */
        $(this.formSelector).on('submit', (e) => this.handleSubmit(e));
    }

    /**
     * Handle the form submission
     * @param {Event} event The form submission event
     */
    handleSubmit(event)
    {
        /**
         * Prevent the default form submission behavior
         */
        event.preventDefault();

        /**
         * Get the form element
         * @type {HTMLFormElement}
         */
        const form = $(event.currentTarget)[0];

        /**
         * Create a new FormData object
         * @type {FormData}
         */
        const formData = new FormData();

        /**
         * Add all form fields to the FormData object
         */
        const formFields = form.querySelectorAll('input, select, textarea');
        formFields.forEach(
            field => {
                if (field.type === 'checkbox') {
                    /**
                     * Add the checkbox value to the FormData object
                     */
                    formData.append(field.name, field.checked ? field.value : '');
                } else if (field.type === 'radio') {
                    /**
                     * Add the radio value to the FormData object
                     */
                    if (field.checked) {
                        formData.append(field.name, field.value);
                    }
                } else {
                    /**
                     * Add the field value to the FormData object
                     */
                    formData.append(field.name, field.value);
                }
            }
        );

        /**
         * Add the required action and nonce to the FormData object
         */
        formData.append('action', 'save_balto_settings');
        formData.append('security', balto_delivery_settings_data.nonce);

        /**
         * Show the loading state
         */
        this.setLoadingState(true);

        /**
         * Make the AJAX call
         */
        $.ajax(
            {
                url: balto_delivery_settings_data.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => this.handleSuccess(response),
                error: (xhr, status, error) => this.handleError(error),
                complete: () => this.setLoadingState(false)
            }
        );
    }

    /**
     * Handle the successful response
     * @param {object} response The response from the AJAX call
     */
    handleSuccess(response)
    {
        if (response.success) {
            /**
             * Show a success notice
             */
            this.showNotice('success', response.data.message);
        } else {
            /**
             * Show an error notice
             */
            this.showNotice('error', response.data.message);
        }
    }

    /**
     * Handle the error response
     * @param {string} error The error message
     */
    handleError(error)
    {
        /**
         * Show an error notice
         */
        this.showNotice('error', balto_delivery_settings_data.i18n.errorMessage);
    }

    /**
     * Set the loading state
     * @param {boolean} isLoading Whether the loading state should be shown
     */
    setLoadingState(isLoading)
    {
        /**
         * Get the submit button element
         * @type {HTMLInputElement}
         */
        const submitButton = $(this.formSelector).find('input[type="submit"]');

        if (isLoading) {
            /**
             * Disable the submit button and change its value to "Saving..."
             */
            submitButton.prop('disabled', true);
            submitButton.val(balto_delivery_settings_data.i18n.saving);
        } else {
            /**
             * Enable the submit button and change its value to "Save Settings"
             */
            submitButton.prop('disabled', false);
            submitButton.val(balto_delivery_settings_data.i18n.saveSettings);
        }
    }

    /**
     * Show a notice
     * @param {string} type The type of notice to show (success or error)
     * @param {string} message The message to show in the notice
     */
    showNotice(type, message)
    {
        /**
         * Get the notice class
         * @type {string}
         */
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';

        /**
         * Create a new notice element
         * @type {HTMLDivElement}
         */
        const notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);

        /**
         * Remove existing notices
         */
        $('.notice').remove();

        /**
         * Add the new notice
         */
        $(this.formSelector).before(notice);

        /**
         * Make the notice dismissible
         */
        if (wp.hasOwnProperty('notices')) {
            wp.notices.removeDismissible();
            wp.notices.addDismissible();
        }

        /**
         * Auto hide after 5 seconds
         */
        setTimeout(
            () => {
                notice.fadeOut(() => notice.remove());
            }, 5000
        );
    }
}

/**
 * Initialize the settings
 */
new balto_delivery_settings();
