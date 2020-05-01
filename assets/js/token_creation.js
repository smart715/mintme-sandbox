import Modal from './components/modal/Modal';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';
import {NotificationMixin} from './mixins/';
import {
    HTTP_OK,
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
    FORBIDDEN_WORDS,
} from './utils/constants';
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
