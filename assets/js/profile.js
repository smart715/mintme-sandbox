import LimitedTextarea from './components/LimitedTextarea.vue';
import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import {minLength, helpers} from 'vuelidate/lib/validators';
const i18nZipcodes = require('i18n-zipcodes');
const xRegExp = require('xregexp');
const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

const notAvailZipCodes = ['', 'AO', 'AG', 'AW', 'BS', 'BZ', 'BJ', 'BM', 'BO', 'BQ', 'BW', 'BF', 'BI', 'CM', 'CF', 'TD', 'KM', 'CD', 'CG', 'CK', 'CI', 'CW', 'DJ', 'DM', 'TL', 'GQ', 'ER', 'FJ', 'TF', 'GA', 'GM', 'GH', 'GD', 'GY', 'HM', 'HK', 'IE', 'KI', 'KP', 'LY', 'MO', 'MW', 'ML', 'MR', 'NA', 'NR', 'NL', 'NU', 'QA', 'RW', 'KN', 'ST', 'SC', 'SL', 'SX', 'SB', 'SR', 'SY', 'TG', 'TK', 'TO', 'TV', 'UG', 'AE', 'VU', 'YE', 'ZW'];

const zipCodeValidation = (zipCode) => {
    try {
        const country = document.getElementById('profile_country').value;
        return i18nZipcodes(country, zipCode);
    } catch (e) {
        return true;
    }
};

new Vue({
    el: '#profile',
    data: {
        showEditForm: false,
        firstName: '',
        lastName: '',
        city: '',
        country: '',
        zipCode: '',
    },
    watch: {
        country: function() {
            if (this.notAvailZipCode) {
                this.zipCode = '';
            }
            this.$refs.zipCode.disabled = this.notAvailZipCode;
            this.$v.zipCode.$touch();
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
        this.country = this.$refs.savedCountry.value;
        this.zipCode = this.$refs.zipCode.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
    },
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
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
            zipCodeValidation,
        },
    },
});
