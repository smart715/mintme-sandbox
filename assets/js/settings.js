import Passwordmeter from './components/PasswordMeter';
import ApiKeys from './components/ApiKeys';
import ApiClients from './components/ApiClients';
import TwoFactorModal from './components/modal/TwoFactorModal';
import {NotificationMixin} from './mixins/';

new Vue({
    el: '#settings',
    components: {Passwordmeter, ApiKeys, ApiClients, TwoFactorModal},
    mixins: [NotificationMixin],
    data: {
        password: '',
        current_password: '',
        disabled: true,
        passwordInput: null,
        isPass: true,
        eyeIcon: null,
        twoFaVisisble: false,
        code: '',
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_change_password_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
        this.disabled = false;
    },
    methods: {
        onPrevent: function(e) {
            e.preventDefault();
            this.twoFaVisisble = true;
        },
        clearInputs: function() {
            this.current_password = '';
            this.password = '';
        },
        doCodeVerify: function(code = '') {
            this.$axios.single.patch(this.$routing.generate('update-password'), {
                current_password: this.current_password,
                plainPassword: this.password,
                code: code,
            })
            .then(() => {
                this.clearInputs();
                this.twoFaVisisble = false;
                this.notifySuccess('Password was updated successfully.');
            }, (error) => {
                if (!error.response) {
                    this.notifyError('Network error');
                } else if (error.response.status === 401) {
                    this.notifyError('Invalid 2FA code');
                } else if (error.response.data.errors) {
                    this.notifyError(error.response.data.errors);
                } else {
                    this.notifyError('An error has occurred, please try again later');
                }
             });
        },
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
