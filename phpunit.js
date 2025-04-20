/**
 * A class to create an enhanced phone input with country flag dropdown
 * @class PhoneInputDropdown
 */
class PhoneInputDropdown {
    /**
     * Create a new PhoneInputDropdown instance
     * @constructor
     * @param {Object} options - Configuration options for the phone input
     * @param {Object} options.countryFlags - Object containing country flag and phone code information
     * @param {string} options.phoneFieldId - ID of the phone input field
     */
    constructor(options = {}) {
        // Default country flags (can be expanded)
        this.countryFlags = options.countryFlags || {
            "United Kingdom": { 
                "flag": "https://mayfairwellnessclinic.com/wp-content/uploads/2025/03/jjjj.png", 
                "code": "+44" 
            }
        };

        // ID of the phone input field
        this.phoneFieldId = options.phoneFieldId || 'billing_phone';

        // Selected country (defaults to first country in the list)
        this.selectedCountry = Object.keys(this.countryFlags)[0];

        // Initialize the dropdown
        this.init();
    }

    /**
     * Initialize the phone input dropdown
     * @method init
     */
    init() {
        // Get the phone input field
        const phoneField = document.getElementById(this.phoneFieldId);
        if (!phoneField) return;

        // Create wrapper for phone input
        this.wrapper = this.createWrapper(phoneField);

        // Create flag dropdown container
        this.flagDropdownContainer = this.createFlagDropdownContainer();

        // Add event listeners
        this.addEventListeners();
    }

    /**
     * Create a wrapper around the phone input field
     * @method createWrapper
     * @param {HTMLElement} phoneField - The phone input field to wrap
     * @returns {HTMLElement} The created wrapper element
     */
    createWrapper(phoneField) {
        const wrapper = document.createElement('div');
        wrapper.className = 'phone-input-wrapper';
        phoneField.parentNode.insertBefore(wrapper, phoneField);
        wrapper.appendChild(phoneField);
        return wrapper;
    }

    /**
     * Create the flag dropdown container
     * @method createFlagDropdownContainer
     * @returns {HTMLElement} The created flag dropdown container
     */
    createFlagDropdownContainer() {
        const flagDropdownContainer = document.createElement('div');
        flagDropdownContainer.className = 'flag-dropdown-container';

        // Create flag image
        const flagImage = this.createFlagImage();

        // Create dropdown arrow
        const dropdownArrow = this.createDropdownArrow();

        // Create dropdown details
        const flagDropdownDetails = this.createFlagDropdownDetails();

        // Assemble the container
        flagDropdownContainer.appendChild(flagImage);
        flagDropdownContainer.appendChild(dropdownArrow);
        flagDropdownContainer.appendChild(flagDropdownDetails);

        // Insert before phone field
        this.wrapper.insertBefore(flagDropdownContainer, this.wrapper.firstChild);

        return flagDropdownContainer;
    }

    /**
     * Create the selected flag image
     * @method createFlagImage
     * @returns {HTMLImageElement} The created flag image element
     */
    createFlagImage() {
        const flagImage = document.createElement('img');
        flagImage.className = 'selected-flag';
        flagImage.src = this.countryFlags[this.selectedCountry].flag;
        return flagImage;
    }

    /**
     * Create the dropdown arrow
     * @method createDropdownArrow
     * @returns {HTMLElement} The created dropdown arrow element
     */
    createDropdownArrow() {
        const dropdownArrow = document.createElement('span');
        dropdownArrow.className = 'dropdown-arrow';
        return dropdownArrow;
    }

    /**
     * Create the flag dropdown details
     * @method createFlagDropdownDetails
     * @returns {HTMLElement} The created dropdown details element
     */
    createFlagDropdownDetails() {
        const flagDropdownDetails = document.createElement('div');
        flagDropdownDetails.className = 'flag-dropdown-details';

        // Populate dropdown with country options
        Object.keys(this.countryFlags).forEach(country => {
            const countryOption = this.createCountryOption(country);
            flagDropdownDetails.appendChild(countryOption);
        });

        return flagDropdownDetails;
    }

    /**
     * Create a country option for the dropdown
     * @method createCountryOption
     * @param {string} country - The country name
     * @returns {HTMLElement} The created country option element
     */
    createCountryOption(country) {
        const countryOption = document.createElement('div');
        countryOption.className = 'country-option';
        
        // Create flag image
        const optionFlag = document.createElement('img');
        optionFlag.src = this.countryFlags[country].flag;
        optionFlag.alt = `${country} flag`;

        // Create country details text
        const optionDetails = document.createElement('span');
        optionDetails.className = 'country-text';
        optionDetails.textContent = `${country} (${this.countryFlags[country].code})`;

        // Assemble the option
        countryOption.appendChild(optionFlag);
        countryOption.appendChild(optionDetails);

        return countryOption;
    }

    /**
     * Add event listeners to the dropdown
     * @method addEventListeners
     */
    addEventListeners() {
        const flagDropdownDetails = this.flagDropdownContainer.querySelector('.flag-dropdown-details');

        // Toggle dropdown on container click
        this.flagDropdownContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            flagDropdownDetails.style.display = 
                flagDropdownDetails.style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            flagDropdownDetails.style.display = 'none';
        });
    }

    /**
     * Add a new country to the dropdown
     * @method addCountry
     * @param {string} country - Country name
     * @param {Object} details - Country flag and phone code details
     */
    addCountry(country, details) {
        this.countryFlags[country] = details;
    }

    /**
     * Get the currently selected country
     * @method getSelectedCountry
     * @returns {string} The name of the selected country
     */
    getSelectedCountry() {
        return this.selectedCountry;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PhoneInputDropdown({
        countryFlags: {
            "United Kingdom": { 
                "flag": "https://mayfairwellnessclinic.com/wp-content/uploads/2025/03/jjjj.png", 
                "code": "+44" 
            }
        }
    });
});