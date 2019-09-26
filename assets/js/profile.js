import LimitedTextarea from './components/LimitedTextarea.vue';
import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import {minLength, helpers} from 'vuelidate/lib/validators';
const postalCodes = require('postal-codes-js');
const xRegExp = require('xregexp');
const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

const zipCodeValidation = (zipCode) => {
    const country = document.getElementById('profile_country').value;
    true === postalCodes.validate(country, zipCode);
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
            if ('' === this.country) {
                this.$refs.zipCode.value = '';
            }
            this.$refs.zipCode.disabled = '' === this.country;
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
