import Passwordmeter from './components/PasswordMeter';
import ApiKeys from './components/ApiKeys';

new Vue({
    el: '#settings',
    components: {Passwordmeter, ApiKeys},
    data: {
        password: '',
        disabled: false,
        passwordInput: null,
        isPass: true,
        eyeIcon: null,
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_change_password_plainPassword');
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
