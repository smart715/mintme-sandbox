import Passwordmeter from '../components/PasswordMeter';
import ApiKeys from '../components/ApiKeys';
import ApiClients from '../components/ApiClients';
import TwoFactorModal from '../components/modal/TwoFactorModal';
import {NotificationMixin} from '../mixins/';
import {HTTP_UNAUTHORIZED} from '../utils/constants';
import i18n from '../utils/i18n/i18n';
import NotificationsManagementModal from '../components/modal/NotificationsManagementModal';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEye} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faEye);

new Vue({
    el: '#settings',
    i18n,
    components: {
        Passwordmeter,
        ApiKeys,
        ApiClients,
        TwoFactorModal,
        NotificationsManagementModal,
        FontAwesomeIcon,
    },
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
        notificationConfigModalVisible: false,
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_change_password_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
    },
    methods: {
        notificationConfigModalToggle: function() {
            this.notificationConfigModalVisible = !this.notificationConfigModalVisible;
        },
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
                        this.notifyError(this.$t('toasted.error.try_later'));
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
                this.notifySuccess(this.$t('toasted.success.password_updated'));
            }, (error) => {
                if (!error.response) {
                    this.notifyError(this.$t('toasted.error.network'));
                } else if (HTTP_UNAUTHORIZED === error.response.status) {
                    this.notifyError(this.$t('page.settings_invalid_2fa'));
                } else if (error.response.data.message) {
                    this.notifyError(error.response.data.message);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
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
