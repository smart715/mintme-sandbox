import BbcodeEditor from './components/bbcode/BbcodeEditor.vue';
import BbcodeHelp from './components/bbcode/BbcodeHelp.vue';
import BbcodeView from './components/bbcode/BbcodeView.vue';
import Avatar from './components/Avatar.vue';
import PhoneNumber from './components/profile/PhoneNumber.vue';
import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength} from 'vuelidate/lib/validators';
import {zipCodeContain} from './utils/constants.js';
import {HTTP_ACCEPTED} from './utils/constants.js';
import Guide from './components/Guide';
import {names, allNames, nickname} from './utils/constants';
import i18n from './utils/i18n/i18n';
const REGEX_CHINESE = /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff\uff66-\uff9f]/;
const nameRequired = function(val, other) {
    return !val && other;
};

new Vue({
    el: '#profile',
    i18n,
    components: {
        PhoneNumber,
        BbcodeEditor,
        BbcodeHelp,
        BbcodeView,
        LimitedTextarea,
        Avatar,
        Guide,
    },
    data() {
        return {
            showEditForm: false,
            nickname: '',
            phoneNumber: document.getElementById('profile_phoneNumber_phoneNumber')
                ? document.getElementById('profile_phoneNumber_phoneNumber').getAttribute('value')
                : null,
            firstName: '',
            lastName: '',
            country: '',
            city: '',
            zipCode: '',
            zipCodeValid: true,
            zipCodeVaidationPattern: false,
            zipCodeProcessing: false,
            firstNameAux: false,
            lastNameAux: false,
            isValidPhone: false,
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
        phoneChange: function(phone) {
            this.phoneNumber = phone;
        },
        validPhone: function(isValidPhone) {
            this.isValidPhone = isValidPhone;
        },
        validation: function(event) {
            if (event.target.id ==='profile_firstName') {
                let hasChinese = this.firstName.match(REGEX_CHINESE);
                if (hasChinese) {
                    this.firstNameAux = false;
                } else {
                    this.firstNameAux = this.firstName.length < 2;
                }
            }
            if (event.target.id ==='profile_lastName') {
                let hasChinese = this.lastName.match(REGEX_CHINESE);
                if (hasChinese) {
                    this.lastNameAux = false;
                } else {
                    this.lastNameAux = this.lastName.length < 2;
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
                    this.$toasted.error(this.$t('toasted.error.try_later'));
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
            return this.$v.$invalid ||
                !this.zipCodeValid ||
                this.zipCodeProcessing ||
                this.firstNameAux ||
                this.lastNameAux ||
                !this.isValidPhone;
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
                helpers: allNames,
            },
            lastName: {
                required: (val) => !nameRequired(val, this.firstName),
                helpers: allNames,
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
