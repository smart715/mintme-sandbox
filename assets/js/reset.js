import Passwordmeter from './components/PasswordMeter';
import i18n from './utils/i18n/i18n';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEye} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faEye);

new Vue({
    el: '#reset',
    i18n,
    components: {
        Passwordmeter,
        FontAwesomeIcon,
    },
    data: {
        password: '',
        disabled: false,
        passwordInput: null,
        isPass: true,
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
        togglePassword: function() {
            if (this.isPass) {
                this.passwordInput.type = 'text';
                this.eyeIcon.className = 'show-password-active';
                this.isPass = false;
            } else {
                this.passwordInput.type = 'password';
                this.eyeIcon.className = 'show-password';
                this.isPass = true;
            }
        },
    },
});
