import LimitedTextarea from './components/LimitedTextarea.vue';
import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import {minLength, helpers} from 'vuelidate/lib/validators';
import {notAvailZipCodes, zipCodeContain} from './utils/constants.js';
const i18nZipcodes = require('i18n-zipcodes');
const xRegExp = require('xregexp');
const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

const zipCodeValidation = (zipCode) => {
    if ('' === zipCode) {
        return true;
    }

    try {
        const country = document.getElementById('profile_country').value;
        return i18nZipcodes(country, zipCode);
    } catch (e) {
        return true;
    }
};

new Vue({
    el: '#profile',
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
    },
    data() {
        return {
            showEditForm: false,
            firstName: '',
            lastName: '',
            city: '',
            country: '',
            zipCode: '',
        };
    },
    watch: {
        country: function() {
            const savedCode = this.zipCode;
            this.zipCode = '';
            this.$v.zipCode.$reset();
            if (!this.notAvailZipCode) {
                this.zipCode = savedCode;
            }
            this.$refs.zipCode.disabled = this.notAvailZipCode;
            this.$refs.zipCode.setAttribute(
                'placeholder',
                this.notAvailZipCode
                    ? ('' === this.country ? 'Select the country for set up zip code' : 'Selected country has no zip codes')
                    : ''
            );
        },
    },
    computed: {
        notAvailZipCode: function() {
            return -1 !== notAvailZipCodes.indexOf(this.country.toUpperCase());
        },
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.city = this.$refs.city.getAttribute('value');
        this.country = this.$refs.country.value;
        this.zipCode = this.$refs.zipCode.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
        this.$refs.zipCode.disabled = this.notAvailZipCode;
    },
    methods: {
        countryChanged: function() {
            this.country = this.$refs.country.value;
        },
    },
    validations: {
        firstName: {
            helpers: names,
            minLength: minLength(2),
        },
        lastName: {
            helpers: names,
            minLength: minLength(2),
        },
        city: {
            helpers: names,
            minLength: minLength(2),
        },
        zipCode: {
            zipCodeContain,
            zipCodeValidation,
        },
    },
});
