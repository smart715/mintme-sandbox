import Passwordmeter from './PasswordMeter';
import Guide from './Guide';
import {required, minLength} from 'vuelidate/lib/validators';
import {nickname, email, emailLength} from '../utils/constants';
import i18n from '../utils/i18n/i18n';
import {NoBadWordsMixin, OpenPageMixin, TogglePassword} from '../mixins';

export default {
    i18n,
    components: {
        Passwordmeter,
        Guide,
    },
    mixins: [
        OpenPageMixin,
        NoBadWordsMixin,
        TogglePassword,
    ],
    data() {
        return {
            nickname: '',
            email: '',
            password: '',
            disabled: false,
            passwordInput: null,
            isPassVisible: true,
            eyeIcon: null,
            nicknameBadWordMessage: '',
            emailBadWordMessage: '',
            termsCheckboxValue: false,
        };
    },
    computed: {
        btnDisabled() {
            return this.disabled || this.$v.$invalid;
        },
    },
    methods: {
        toggleError: function(val) {
            this.disabled = val;
        },
    },
    mounted() {
        this.passwordInput = this.$refs['password-input'];
        this.eyeIcon = this.$refs['eye-icon'];
        this.nickname = this.$refs.nickname.getAttribute('value');
        this.email = this.$refs.email.getAttribute('value');
    },
    validations() {
        return {
            nickname: {
                required,
                helpers: nickname,
                minLength: minLength(2),
                noBadWords: () => this.noBadWordsValidator('nickname', 'nicknameBadWordMessage'),
            },
            email: {
                required,
                helpers: email,
                length: emailLength,
                minLength: minLength(2),
                noBadWords: () => this.noBadWordsValidator('email', 'emailBadWordMessage'),
            },
            password: {
                required,
            },
            termsCheckboxValue: {
                checked: () => this.termsCheckboxValue,
            },
        };
    },
};
