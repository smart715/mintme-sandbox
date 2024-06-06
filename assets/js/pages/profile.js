import '../../scss/pages/profile.sass';
import {VBTooltip} from 'bootstrap-vue';
import {CountedTextarea, MInput, MSelect, FormControlWrapper, MTextarea} from '../components/UI';
import PlainTextView from '../components/UI/PlainTextView';
import Avatar from '../components/Avatar.vue';
import CoinAvatar from '../components/CoinAvatar';
import PhoneNumber from '../components/profile/PhoneNumber.vue';
import BlockUser from '../components/profile/BlockUser';
import LimitedTextarea from '../components/LimitedTextarea.vue';
import TokensUserOwns from '../components/profile/TokensUserOwns.vue';
import {maxLength, minLength} from 'vuelidate/lib/validators';
import {HTTP_ACCEPTED, zipCodeContain} from '../utils/constants.js';
import Guide from '../components/Guide';
import {allNames, names, nickname} from '../utils/constants';
import i18n from '../utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faInfoCircle, faLongArrowAltLeft} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {NoBadWordsMixin} from '../mixins';
import ProfileInit from '../components/profile/ProfileInit';
import {mapGetters} from 'vuex';
import store from '../storage';

const REGEX_CHINESE = /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff\uff66-\uff9f]/;

const nameRequired = function(val, other) {
    return !val && other;
};

const nicknameRequired = function(val, other) {
    return !val && !other;
};

library.add(faLongArrowAltLeft, faEdit, faInfoCircle);

new Vue({
    el: '#profile',
    delimiters: ['${', '}'],
    i18n,
    components: {
        PhoneNumber,
        CountedTextarea,
        PlainTextView,
        LimitedTextarea,
        Avatar,
        Guide,
        FontAwesomeIcon,
        TokensUserOwns,
        MInput,
        MSelect,
        MTextarea,
        FormControlWrapper,
        ProfileInit,
        BlockUser,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [NoBadWordsMixin],
    data() {
        return {
            showEditForm: false,
            nickname: '',
            firstName: '',
            lastName: '',
            selectedCountry: '',
            city: '',
            zipCode: '',
            description: '',
            zipCodeValid: true,
            zipCodeVaidationPattern: false,
            zipCodeProcessing: false,
            firstNameAux: false,
            lastNameAux: false,
            isValidPhone: true,
            isPhoneRequired: true,
            initialPhoneNumber: null,
            phoneNumberModel: null,
            isFirstNameRequired: false,
            isLastNameRequired: false,
            zipCodeDisabled: true,
            loaded: false,
            nicknameBadWordMessage: '',
            descriptionBadWordMessage: '',
            isPageReady: false,
        };
    },
    beforeMount() {
        const phoneElement = document.getElementById('profile_phoneNumber_phoneNumber');
        this.phoneNumberModel = phoneElement ? phoneElement.getAttribute('value') : null;
        this.initialPhoneNumber = this.phoneNumberModel;
    },
    mounted: function() {
        this.nickname = this.profile.nickname;
        this.firstName = this.profile.firstName;
        this.lastName = this.profile.lastName;
        this.selectedCountry = this.profile.country;
        this.city = this.profile.city;
        this.zipCode = this.profile.zipCode;
        this.description = this.profile.description;

        this.isPhoneRequired = !!this.phoneNumberModel;
        this.isFirstNameRequired = !!this.firstName;
        this.isLastNameRequired = !!this.lastName;


        this.showEditForm = !!this.$refs.editFormShowFirst.value;
        this.countryChanged();
        this.loaded = true;
    },
    methods: {
        toggleZipCodeInputDisabled: function(state) {
            this.zipCodeDisabled = state;
        },
        phoneChange: function(phone) {
            this.phoneNumberModel = phone;
        },
        validPhone: function(isValidPhone) {
            this.isValidPhone = isValidPhone;
        },
        validation: function(fieldName) {
            if ('profile_nickname' === fieldName && !this.nickname) {
                this.nickname = '';
            }

            if ('profile_firstName' === fieldName) {
                const hasChinese = this.firstName.match(REGEX_CHINESE);
                if (hasChinese || (!this.isFirstNameRequired && 0 === this.firstName.length)) {
                    this.firstNameAux = false;
                } else {
                    this.firstNameAux = 2 > this.firstName.length;
                }
            }
            if ('profile_lastName' === fieldName) {
                const hasChinese = this.lastName.match(REGEX_CHINESE);
                if (hasChinese || (!this.isLastNameRequired && 0 === this.lastName.length)) {
                    this.lastNameAux = false;
                } else {
                    this.lastNameAux = 2 > this.lastName.length;
                }
            }
        },
        countryChanged: function() {
            if (!this.$refs.zipCode) {
                return;
            }

            this.selectedCountry = this.$refs.country.value;

            this.toggleZipCodeInputDisabled(true);
            if ('' === this.selectedCountry) {
                this.zipCode = '';
            }

            this.zipCodeProcessing = true;
            this.$axios.single.post(this.$routing.generate('validate_zip_code'), {
                country: this.selectedCountry,
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
                const regex = new RegExp('^' + this.zipCodeVaidationPattern + '$', 'i');
                this.zipCodeValid = regex.test(this.zipCode);
            }
        },
        descriptionChanged: function(description) {
            this.description = description;
        },
        codeResend: function() {
            this.$refs.submitBtn.click();
        },
    },
    computed: {
        ...mapGetters('profile', {
            profile: 'getProfile',
            countries: 'getCountries',
            countriesMap: 'getCountriesMap',
        }),
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
                required: (val) => !nicknameRequired(val, this.nickname),
                helpers: nickname,
                minLength: minLength(2),
                maxLength: maxLength(30),
                noBadWords: () => this.noBadWordsValidator('nickname', 'nicknameBadWordMessage'),
            },
            description: {
                noBadWords: () => this.noBadWordsValidator('description', 'descriptionBadWordMessage'),
                maxLength: maxLength(500),
            },
            firstName: {
                required: (val) => !nameRequired(val, this.lastName),
                helpers: allNames,
                maxLength: maxLength(30),
            },
            lastName: {
                required: (val) => !nameRequired(val, this.firstName),
                helpers: allNames,
                maxLength: maxLength(30),
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

                    return 0 < zipCode.replace(/\s/g, '').length;
                },
            },
        };
    },
    store,
});
