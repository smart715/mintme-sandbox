import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength} from 'vuelidate/lib/validators';
import {zipCodeContain} from './utils/constants.js';
import {HTTP_ACCEPTED} from './utils/constants.js';
import Guide from './components/Guide';
import {names, nickname} from './utils/constants';

const REGEX_CHINESE = /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff\uff66-\uff9f]/;
const nameRequired = function(val, other) {
    return !val && other;
};

new Vue({
    el: '#profile',
    components: {
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
        Guide,
    },
    data() {
        return {
            showEditForm: false,
            nickname: '',
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
        this.nickname = this.$refs.nickname.getAttribute('value');
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

        validation: function(event) {
            if (event.target.id ==='profile_firstName') {
                let hasChinese = this.firstName.match(REGEX_CHINESE);
                if (hasChinese) {
                  // this means only chinese characters are typed
                    this.firstNameAux = false;
                } else {
                    // this means regular characters are typed
                    if (this.firstName.length < 2) {
                        this.firstNameAux = true;
                    } else {
                        this.firstNameAux = false;
                    }
                }
            }
            if (event.target.id ==='profile_lastName') {
                let hasChinese = this.lastName.match(REGEX_CHINESE);
                if (hasChinese) {
                    // this means only chinese characters are typed
                    this.lastNameAux = false;
                } else {
                    // this means regular characters are typed
                    if (this.lastName.length < 2) {
                        this.lastNameAux = true;
                    } else {
                        this.lastNameAux = false;
                    }
                }
            }
        },
        countryChanged: function() {
            if (!this.$refs.zipCode) {
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
    computed: {
        disableSave: function() {
            return this.$v.$invalid || !this.zipCodeValid || this.zipCodeProcessing;
        },
    },
    validations() {
        return {
            nickname: {
                helpers: nickname,
                minLength: minLength(2),
            },
            firstName: {
                required: (val) => !nameRequired(val, this.lastName),
                helpers: names,
                // minLength: minLength(2),
            },
            lastName: {
                required: (val) => !nameRequired(val, this.firstName),
                helpers: names,
                // minLength: minLength(2),
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
        };
    },
});
