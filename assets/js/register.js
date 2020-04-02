import Passwordmeter from './components/PasswordMeter';

new Vue({
    el: '#register',
    components: {Passwordmeter},
    data: {
        password: '',
        disabled: false,
        passwordInput: null,
        isPass: true,
        eyeIcon: null,
    },
    mounted() {
        this.passwordInput = document.getElementById('fos_user_registration_form_plainPassword');
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
