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
            this.matchPasswords();
        },
        clearInputs: function() {
            this.current_password = '';
            this.password = '';
        },
        matchPasswords: function () {
            this.disabled = true;
            let customError = document.getElementById('custom-error');

            if (customError!== null) {
                customError.remove();
            }
            this.$axios.single.patch(this.$routing.generate('match-password'), {
                current_password: this.current_password,
                plainPassword: this.password,
            })
                .then(() => {
                    this.disabled = false;
                    this.twoFaVisisble = true;
                }, (error) => {
                    this.disabled = false;
                    if (!error.response) {
                        this.notifyError('Network error');
                    } else if (error.response.data.message) {
                        let currentPasswordInput = document.getElementById('app_user_change_password_current_password');
                        let html_to_insert = '<div id="custom-error" class="py-2 mb-2 bg-danger text-white text-center">' +
                                                '<ul class="pl-3 pr-3 m-0 list-unstyled">' +
                                                    '<li>The entered password is invalid.</li>' +
                                                '</ul>' +
                                             '</div>';
                        currentPasswordInput.insertAdjacentHTML('beforebegin', html_to_insert);
                    } else {
                        this.notifyError('An error has occurred, please try again later');
                    }
                });
        },
        doChangePassword: function(code = '') {
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
                } else if (401 === error.response.status) {
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
