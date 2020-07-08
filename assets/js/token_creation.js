import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin} from './mixins/';
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
    mixins: [NotificationMixin],
    data() {
        return {
            domLoaded: false,
            tokenName: '',
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
            tokenNameInBlacklist: false,
        };
    },
    computed: {
        saveBtnDisabled: function() {
            return this.$v.$anyError || !this.tokenName ||
                this.tokenNameExists || this.tokenNameProcessing;
        },
    },
    watch: {
        tokenName: function() {
            clearTimeout(this.tokenNameTimeout);

            if (this.tokenName.replace(/-|\s/g, '').length === 0) {
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
                                            this.notifyError('An error has occurred, please try again later');
                                        })
                                        .then(() => {
                                            this.tokenNameProcessing = false;
                                        });
                                }
                            }
                        }, (error) => {
                            this.notifyError('An error has occurred, please try again later');
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
            let frm = document.querySelector('form');
            let frmData = new FormData(frm);
            this.$axios.single.post(this.$routing.generate('token_create'), frmData)
                .then((res) => {
                    if (res.status === HTTP_ACCEPTED) {
                        location.href = this.$routing.generate('token_show', {
                            name: this.tokenName,
                            alert: true,
                        });
                    }
                }, (err) => this.notifyError(err.response.data.message));
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
            maxLength: maxLength(255),
        },
    },
});
