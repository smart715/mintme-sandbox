import '../../scss/pages/token_creation.sass';
import LimitedTextarea from '../components/LimitedTextarea';
import Guide from '../components/Guide';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin, AddPhoneAlertMixin, NoBadWordsMixin} from '../mixins/';
import AddPhoneAlertModal from '../components/modal/AddPhoneAlertModal';
import i18n from '../utils/i18n/i18n';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
    FORBIDDEN_WORDS,
    descriptionLength,
} from '../utils/constants';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheck, faExclamationCircle, faLongArrowAltLeft} from '@fortawesome/free-solid-svg-icons';
import {faCheckSquare} from '@fortawesome/fontawesome-free-regular';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {MButton, MInput, MTextarea} from '../components/UI';
import store from '../storage';
import {mapGetters} from 'vuex';

library.add(faExclamationCircle, faLongArrowAltLeft, faCheckSquare, faCheck);

new Vue({
    el: '#token_creation',
    i18n,
    mixins: [NotificationMixin, AddPhoneAlertMixin, NoBadWordsMixin],
    delimiters: ['${', '}'],
    components: {
        LimitedTextarea,
        FontAwesomeIcon,
        Guide,
        AddPhoneAlertModal,
        MButton,
        MInput,
        MTextarea,
    },
    data() {
        return {
            domLoaded: false,
            tokenName: '',
            description: '',
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameValidating: false,
            tokenNameInBlacklist: false,
            descriptionLength: descriptionLength,
            tokenCreation: true,
            noClose: true,
            addPhoneModalMessageType: 'token_create',
            handlingSubmit: false,
            descriptionTimeout: null,
            descriptionBadWordMessage: '',
            tokenNameTimeout: null,
            tokenNameBadWordMessage: '',
            needPhoneVerified: false,
        };
    },
    computed: {
        ...mapGetters('user', {
            hasPhoneVerified: 'getHasPhoneVerified',
        }),
        saveBtnDisabled: function() {
            return this.$v.$invalid ||
                !this.tokenName ||
                this.tokenNameExists ||
                this.tokenNameProcessing ||
                this.tokenNameValidating ||
                this.handlingSubmit;
        },
        translationsContext: function() {
            return {
                maxDescriptionLength: this.descriptionLength.max,
                minDescriptionLength: this.descriptionLength.min,
            };
        },
        isDescriptionIsFilled() {
            return (this.description || '').length >= this.descriptionLength.min;
        },
    },
    methods: {
        closeAddPhoneModal: function() {
            if (!this.hasPhoneVerified) {
                1 < window.history.length
                    ? window.history.back()
                    : window.location.href = '/';
            }
        },
        redirectToProfile: function() {
            location.href = this.$routing.generate('profile-view');
        },
        createToken: function(e) {
            e.preventDefault();

            if (!this.tokenCreation) {
                this.notifyError(this.$t('token.creation.disabled'));
                return;
            }

            if (this.handlingSubmit || (this.needPhoneVerified && !this.hasPhoneVerified)) {
                return;
            }

            this.handlingSubmit = true;

            const frm = document.querySelector('form[name="token_create"]');
            const frmData = new FormData(frm);
            this.$axios.single.post(this.$routing.generate('token_create'), frmData)
                .then((res) => {
                    if (res.status === HTTP_OK) {
                        window.location = this.$routing.generate('token_show_intro', {
                            name: this.tokenName,
                            modal: 'created',
                        });
                    }
                })
                .catch((err) => {
                    this.notifyError(err.response.data.message);
                    this.handlingSubmit = false;
                });
        },
        tokenInvalid: function(e) {
            e.target.setCustomValidity('Invalid token name.');
            e.target.title = 'Invalid token name.';
        },
        tokenChange: function(e) {
            e.target.setCustomValidity('');
            e.target.title = '';
        },
        tokenInput: function(e) {
            e.target.setCustomValidity('');
            e.target.title = '';
        },
        isTokenNameBlacklistedRequest: async function() {
            return this.$axios.single.get(
                this.$routing.generate('token_name_blacklist_check', {name: this.tokenName}
                ));
        },
        isTokenNameExistRequest: async function() {
            return this.$axios.single.get(
                this.$routing.generate('check_token_name_exists', {name: this.tokenName}
                ));
        },
        tokenNameValidate: async function() {
            if (this.$v.tokenName.$invalid) {
                return;
            }

            try {
                const blacklistResponse = await this.isTokenNameBlacklistedRequest();

                if (HTTP_OK === blacklistResponse.status) {
                    this.tokenNameInBlacklist = blacklistResponse.data.blacklisted;

                    if (!this.tokenNameInBlacklist) {
                        const tokenNameExistResponse = await this.isTokenNameExistRequest();
                        if (HTTP_OK === tokenNameExistResponse.status) {
                            this.tokenNameExists = tokenNameExistResponse.data.exists;
                        }
                    }
                }
            } catch (error) {
                this.notifyError(this.$t('toasted.error.try_later'));
            } finally {
                this.tokenNameProcessing = false;
            }
        },
        onPhoneVerified() {
            this.addPhoneModalVisible = false;
        },
    },
    mounted: function() {
        window.onload = () => this.domLoaded = true;
        this.needPhoneVerified = !!this.$refs.tokenCreateError.value;

        if (this.needPhoneVerified) {
            this.addPhoneModalVisible = true;
        } else {
            this.$refs.tokenNameInput.focus();
        }

        this.$axios.single.get(this.$routing.generate('check_token_creation'))
            .then((result) => {
                if (result.data) {
                    this.tokenCreation = result.data.tokenCreation;
                }
            });
    },
    validations() {
        return {
            tokenName: {
                required,
                validFirstChars: (value) => !tokenValidFirstChars(value),
                validLastChars: (value) => !tokenValidLastChars(value),
                noSpaceBetweenDashes: (value) => !tokenNoSpaceBetweenDashes(value),
                hasNotBlockedWords: (value) => !FORBIDDEN_WORDS.some(
                    (blocked) =>
                        new RegExp('\\b' + blocked + 's{0,1}\\b', 'ig').test(value)
                ),
                validChars: tokenNameValidChars,
                minLength: minLength(4),
                maxLength: maxLength(60),
                noBadWords: () => this.noBadWordsValidator('tokenName', 'tokenNameBadWordMessage'),
                tokenNameValidate: () => {
                    if (this.tokenNameTimeout) {
                        clearTimeout(this.tokenNameTimeout);
                    }

                    this.tokenNameTimeout = setTimeout(this.tokenNameValidate, 1000);
                    this.tokenNameProcessing = true;

                    return true;
                },
            },
            description: {
                required: (val) => required(val.trim()),
                minLength: minLength(descriptionLength.min),
                maxLength: maxLength(descriptionLength.max),
                noBadWords: () => this.noBadWordsValidator('description', 'descriptionBadWordMessage'),
            },
        };
    },
    store,
});
