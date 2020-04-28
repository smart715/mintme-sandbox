import Modal from './components/modal/Modal';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin} from './mixins/';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
} from './utils/constants';
const FORBIDDEN_WORDS = ['token', 'coin'];
new Vue({
    el: '#token',
    mixins: [NotificationMixin],
    components: {
        Modal,
    },
    data() {
        return {
            domLoaded: false,
            tokenName: '',
            tokenNameExists: false,
            tokenNameProcessing: false,
            tokenNameTimeout: null,
        };
    },
    computed: {
        saveBtnDisabled: function() {
            return this.$v.$anyError || !this.tokenName || this.tokenNameExists || this.tokenNameProcessing;
        },
    },
    watch: {
        tokenName: function() {
            clearTimeout(this.tokenNameTimeout);

            if (this.tokenName.replace(/-|\s/g, '').length === 0) {
                this.tokenName = '';
            }

            this.tokenNameExists = false;
            if (!this.$v.tokenName.$invalid && this.tokenName) {
                this.tokenNameProcessing = true;
                this.tokenNameTimeout = setTimeout(() => {
                    this.$axios.single.get(this.$routing.generate('check_token_name_exists', {name: this.tokenName}))
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
                }, 2000);
            }
        },
    },
    methods: {
        redirectToProfile: function() {
            location.href = this.$routing.generate('profile-view');
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
            hasBlockedWords: (value) => {
                for (const i of FORBIDDEN_WORDS) {
                    let postFixpattern = '(\w*\\s'+i+')(s+\\b|\\b)';
                    let singleWordpattern = '(^'+i+')(s+\\b|\\b)';
                    let postFixRegex = new RegExp(postFixpattern, 'ig');
                    let singleWordregex = new RegExp(singleWordpattern, 'ig');
                    if (null !== value.match(postFixRegex) || null !== value.match(singleWordregex)) {
                        return false;
                    }
                }
                return true;
            },
            validChars: tokenNameValidChars,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
