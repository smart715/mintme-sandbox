import Passwordmeter from './components/PasswordMeter';
import i18n from './utils/i18n/i18n';
import {TogglePassword} from './mixins';

new Vue({
    el: '#reset',
    i18n,
    components: {
        Passwordmeter,
    },
    mixins: [TogglePassword],
    data: {
        password: '',
        disabled: false,
        passwordInput: null,
        isPassVisible: true,
        eyeIcon: null,
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_resetting_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
    },
    methods: {
        toggleError: function(val) {
            this.disabled = val;
        },
    },
});
