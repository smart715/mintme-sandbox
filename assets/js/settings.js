import Passwordmeter from './components/PasswordMeter';
import ApiKeys from './components/ApiKeys';
import ApiClients from './components/ApiClients';
import TwoFactorModal from './components/modal/TwoFactorModal';
import {NotificationMixin} from './mixins/';
import {HTTP_UNAUTHORIZED} from './utils/constants';

new Vue({
    el: '#settings',
    components: {Passwordmeter, ApiKeys, ApiClients, TwoFactorModal},
    mixins: [NotificationMixin],
    data: {
        password: '',
        currentPassword: '',
        disabled: false,
        passwordInput: null,
        isPass: true,
        eyeIcon: null,
        twoFaVisible: false,
        code: '',
        showErrorMessage: false,
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_change_password_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
    },
    methods: {
        submit2FA: function() {
            this.doCheckStoredUserPassword();
        },
        clearInputs: function() {
            this.currentPassword = '';
            this.password = '';
        },
        doCheckStoredUserPassword: function() {
            this.disabled = true;
            this.showErrorMessage = false;

            this.$axios.single.patch(this.$routing.generate('check-user-password'), {
                current_password: this.currentPassword,
                plainPassword: this.password,
            })
                .then(() => {
                    this.disabled = false;
                    this.twoFaVisible = true;
                }, (error) => {
                    this.disabled = false;
                    if (!error.response) {
                        this.notifyError('Network error');
                    } else if (error.response.data.message) {
                        this.showErrorMessage = true;
                    } else {
                        this.notifyError('An error has occurred, please try again later');
                    }
                });
        },
        doChangePassword: function(code = '') {
            this.$axios.single.patch(this.$routing.generate('update-password'), {
                current_password: this.currentPassword,
                plainPassword: this.password,
                code: code,
            })
            .then(() => {
                this.clearInputs();
                this.twoFaVisible = false;
                this.notifySuccess('Password was updated successfully.');
            }, (error) => {
                if (!error.response) {
                    this.notifyError('Network error');
                } else if (HTTP_UNAUTHORIZED === error.response.status) {
                    this.notifyError('Invalid 2FA code');
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
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
