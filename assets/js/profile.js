import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength} from 'vuelidate/lib/validators';
import {zipCodeContain} from './utils/constants.js';
import {profileNameContain, HTTP_ACCEPTED} from './utils/constants.js';

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
        this.city = this.$refs.city.getAttribute('value');
        this.zipCode = this.$refs.zipCode.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
        this.toggleZipCodeInputDisabled(this.notAvailZipCode);
        this.countryChanged();
    },
    methods: {
        toggleZipCodeInputDisabled: function(state) {
            this.$refs.zipCode.disabled = state;
        },
        countryChanged: function() {
            this.country = this.$refs.country.value;

            if ('' === this.country) {
                this.toggleZipCodeInputDisabled(true);
                this.zipCode = '';
            } else {
                this.toggleZipCodeInputDisabled(false);
            }

            this.zipCodeProcessing = true;
            this.$axios.single.post(this.$routing.generate('validate_zip_code'), {
                country: this.country,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
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
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
                        this.$toasted.error(error.response.data.message);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                })
                .then(() => {
                    this.zipCodeProcessing = false;
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
            helpers: profileNameContain,
            minLength: minLength(2),
        },
        lastName: {
            helpers: profileNameContain,
            minLength: minLength(2),
        },
        city: {
            helpers: profileNameContain,
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
