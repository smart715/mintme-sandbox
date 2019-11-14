import _ from 'lodash';
import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength} from 'vuelidate/lib/validators';
import {names, zipCodeContain} from './utils/constants.js';
import {zipCodeAvailable, zipCodeValidate} from './utils/zipcodevalidator.js';

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
            this.$refs.zipCode.setAttribute('placeholder', this.selectCountryPlaceholder);
        },
    },
    computed: {
        notAvailZipCode: function() {
            return !zipCodeAvailable(this.country);
        },
        selectCountryPlaceholder: function() {
            if (!this.notAvailZipCode) {
                return '';
            }

            if ('' === this.country) {
                return 'Select the country for set up zip code';
            }

            return 'Selected country has no zip codes';
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
            zipCodeValidation: function(zipCode) {
                const countryCode = _.toString(this.country);
                zipCode = _.toString(zipCode);

                if ('' === countryCode || '' === zipCode || !zipCodeAvailable(countryCode)) {
                    return true;
                }

                return zipCodeValidate(countryCode, zipCode);
            },
        },
    },
});
