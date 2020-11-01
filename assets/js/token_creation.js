import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import BbcodeEditor from './components/bbcode/BbcodeEditor';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin} from './mixins/';
import i18n from './utils/i18n/i18n';
import he from 'he';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
    FORBIDDEN_WORDS,
    HTTP_ACCEPTED,
} from './utils/constants';
new Vue({
    el: '#token',
    i18n,
    mixins: [NotificationMixin],
    data() {
        return {
            domLoaded: false,
            tokenName: '',
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
            tokenNameInBlacklist: false,
            newDescription: '',
        };
    },
    components: {
        BbcodeEditor,
        FontAwesomeIcon,
    },
    computed: {
        saveBtnDisabled: function() {
            return this.$v.$anyError || !this.tokenName ||
                this.tokenNameExists || this.tokenNameProcessing;
        },
        newDescriptionHtmlDecode: function() {
            return he.decode(this.newDescription);
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
        redirectToProfile: function() {
            location.href = this.$routing.generate('profile-view');
        },
        createToken: function(e) {
            e.preventDefault();
            let frm = document.querySelector('form[name="token_create"]');
            let frmData = new FormData(frm);
            this.$axios.single.post(this.$routing.generate('token_create'), frmData)
                .then((res) => {
                    if (res.status === HTTP_ACCEPTED) {
                        frm.action = this.$routing.generate('token_show', {
                            name: this.tokenName,
                        });
                        frm.submit();
                    }
                }, (err) => this.notifyError(err.response.data.message));
        },
        onDescriptionChange: function(val) {
            this.newDescription = he.encode(val);
        },

    },
    mounted: function() {
        window.onload = () => this.domLoaded = true;
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
        newDescription: {
            required: (val) => required(val.trim()),
            minLength: minLength(200),
            maxLength: maxLength(10000),
        },
    },
});
