import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import LimitedTextarea from './components/LimitedTextarea';
import Guide from './components/Guide';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin, AddPhoneAlertMixin} from './mixins/';
import AddPhoneAlertModal from './components/modal/AddPhoneAlertModal';
import i18n from './utils/i18n/i18n';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
    FORBIDDEN_WORDS,
    descriptionLength,
} from './utils/constants';

new Vue({
    el: '#token',
    i18n,
    mixins: [NotificationMixin, AddPhoneAlertMixin],
    delimiters: ['${', '}'],
    data() {
        return {
            domLoaded: false,
            tokenName: '',
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
            tokenNameInBlacklist: false,
            description: '',
            tokenCreation: true,
            noClose: true,
            addPhoneModalMessageType: 'token_create',
            handlingSubmit: false,
        };
    },
    components: {
        LimitedTextarea,
        FontAwesomeIcon,
        Guide,
        AddPhoneAlertModal,
    },
    computed: {
        saveBtnDisabled: function() {
            return this.$v.$anyError ||
                !this.tokenName ||
                this.tokenNameExists ||
                this.tokenNameProcessing ||
                this.handlingSubmit;
        },
        translationsContext: function() {
            return {
                maxDescriptionLength: descriptionLength.max,
            };
        },
    },
    watch: {
        tokenName: function() {
            clearTimeout(this.tokenNameTimeout);

            if (this.tokenName.replace(/\s/g, '').length === 0) {
                this.tokenName = '';
            }

            this.tokenNameExists = false;
            this.tokenNameInBlacklist = false;
            if (!this.$v.tokenName.$invalid && this.tokenName) {
                this.tokenNameProcessing = true;
                this.tokenNameTimeout = setTimeout(() => {
                    this.$axios.single.get(this.$routing.generate('token_name_blacklist_check', {name: this.tokenName}))
                        .then((response) => {
                            if (HTTP_OK === response.status) {
                                this.tokenNameInBlacklist = response.data.blacklisted;
                                if (!this.tokenNameInBlacklist) {
                                    this.$axios.single.
                                    get(this.$routing.generate('check_token_name_exists', {name: this.tokenName}))
                                        .then((response) => {
                                            if (HTTP_OK === response.status) {
                                                this.tokenNameExists = response.data.exists;
                                            }
                                        }, (error) => {
                                            this.notifyError(this.$t('toasted.error.try_later'));
                                        })
                                        .then(() => {
                                            this.tokenNameProcessing = false;
                                        });
                                }
                            }
                        }, (error) => {
                            this.notifyError(this.$t('toasted.error.try_later'));
                        });
                    }, 2000);
            }
        },
    },
    methods: {
        closeAddPhoneModal: function() {
            window.history.length > 1
                ? window.history.back()
                : window.location.href = '/';
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

            this.handlingSubmit = true;

            let frm = document.querySelector('form[name="token_create"]');
            let frmData = new FormData(frm);
            this.$axios.single.post(this.$routing.generate('token_create'), frmData)
                .then((res) => {
                    if (res.status === HTTP_OK) {
                        frm.action = this.$routing.generate('token_show', {
                            name: this.tokenName,
                            tab: 'intro',
                            modal: 'created',
                        });
                        frm.submit();
                    }
                }, (err) => this.notifyError(err.response.data.message))
                .finally(()=>{
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
    },
    mounted: function() {
        window.onload = () => this.domLoaded = true;
        if (this.$refs.tokenCreateError.value) {
            this.addPhoneModalVisible = true;
        }
        this.$axios.single.get(this.$routing.generate('check_token_creation'))
            .then((result) => {
                if (result.data) {
                    this.tokenCreation = result.data.tokenCreation;
                }
            });
    },
    validations: {
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
        },
        description: {
            required: (val) => required(val.trim()),
            minLength: minLength(descriptionLength.min),
            maxLength: maxLength(descriptionLength.max),
        },
    },
});
