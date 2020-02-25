import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import PageLoadSpinner from './components/PageLoadSpinner';
import {minLength, helpers} from 'vuelidate/lib/validators';
import {zipCodeContain} from './utils/constants.js';
import {HTTP_ACCEPTED} from './utils/constants.js';
import xRegExp from 'xregexp';

const names = helpers.regex('names', xRegExp('^[\\p{L}]+[\\p{L}\\s\'‘’`´-]*$', 'u'));

new Vue({
    el: '#profile',
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
        PageLoadSpinner,
    },
    data() {
        return {
            showEditForm: false,
            firstName: '',
            lastName: '',
            country: '',
            city: '',
            zipCode: '',
            zipCodeValid: true,
            zipCodeVaidationPattern: false,
            zipCodeProcessing: false,
        };
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.country = this.$refs.country.value;

        if (this.$refs.city) {
            this.city = this.$refs.city.getAttribute('value');
        }

        if (this.$refs.zipCode) {
            this.zipCode = this.$refs.zipCode.getAttribute('value');
        }

        this.showEditForm = !!this.$refs.editFormShowFirst.value;
        this.toggleZipCodeInputDisabled(this.notAvailZipCode);
        this.countryChanged();
    },
    methods: {
        toggleZipCodeInputDisabled: function(state) {
            if (this.$refs.zipCode) {
                this.$refs.zipCode.disabled = state;
            }
        },
        countryChanged: function() {
            if (!this.$refs.zipCode) {
                this.$refs.spinner.hide();
                return;
            }

            this.country = this.$refs.country.value;

            this.toggleZipCodeInputDisabled(true);
            if ('' === this.country) {
                this.zipCode = '';
            }

            this.zipCodeProcessing = true;
            this.$axios.single.post(this.$routing.generate('validate_zip_code'), {
                country: this.country,
            })
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status) {
                        this.zipCodeVaidationPattern = response.data.hasPattern
                            ? response.data.pattern
                            : false;

                        if (false === this.zipCodeVaidationPattern) {
                            this.toggleZipCodeInputDisabled(true);
                            this.zipCode = '';
                        } else {
                            this.toggleZipCodeInputDisabled(false);
                        }

                        this.zipCodeValidate();
                    }
                }, (error) => {
                    this.$toasted.error('An error has occurred, please try again later');
                })
                .then(() => {
                    this.zipCodeProcessing = false;
                    this.$refs.spinner.hide();
                });
        },
        zipCodeValidate: function() {
            if (!this.zipCodeVaidationPattern || !this.zipCode) {
                this.zipCodeValid = true;
            } else {
                let regex = new RegExp('^' + this.zipCodeVaidationPattern + '$', 'i');
                this.zipCodeValid = regex.test(this.zipCode);
            }
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
            zipCodeWrongChars: function(zipCode) {
                if (!zipCode) {
                    return true;
                }

                return zipCode.replace(/\s/g, '').length > 0;
            },
        },
    },
});
