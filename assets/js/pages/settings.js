import '../../scss/pages/settings.sass';
import Passwordmeter from '../components/PasswordMeter';
import ApiKeys from '../components/ApiKeys';
import ApiClients from '../components/ApiClients';
import TwoFactorModal from '../components/modal/TwoFactorModal';
import AddPhoneAlertModal from '../components/modal/AddPhoneAlertModal';
import ChangeEmailModal from '../components/modal/ChangeEmailModal';
import ConfirmModal from '../components/modal/ConfirmModal';
import {
    NotificationMixin,
    OpenPageMixin,
    AddPhoneAlertMixin,
    TogglePassword,
} from '../mixins/';
import {HTTP_UNAUTHORIZED} from '../utils/constants';
import i18n from '../utils/i18n/i18n';
import NotificationsManagementModal from '../components/modal/NotificationsManagementModal';
import Guide from '../components/Guide';
import store from '../storage';
import {mapGetters} from 'vuex';

new Vue({
    el: '#settings',
    i18n,
    components: {
        Guide,
        Passwordmeter,
        ApiKeys,
        ApiClients,
        TwoFactorModal,
        AddPhoneAlertModal,
        NotificationsManagementModal,
        ChangeEmailModal,
        ConfirmModal,
    },
    mixins: [
        NotificationMixin,
        OpenPageMixin,
        AddPhoneAlertMixin,
        TogglePassword,
    ],
    data: {
        password: '',
        currentPassword: '',
        disabled: true,
        passwordInput: null,
        isPassVisible: true,
        eyeIcon: null,
        twoFaVisible: false,
        code: '',
        showErrorMessage: false,
        notificationConfigModalVisible: false,
        loading: false,
        addPhoneModalMessageType: 'enable_2fa',
        changeEmailModalVisible: false,
        showDisconnectDiscordModal: false,
    },
    mounted() {
        this.passwordInput = document.getElementById('app_user_change_password_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
        this.clearHistoryData();

        document.getElementById('disconnect_discord_submit')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showDisconnectDiscordModal = true;
        });
    },
    computed: {
        ...mapGetters('user', {
            hasPhoneVerified: 'getHasPhoneVerified',
        }),
    },
    methods: {
        confirmDisconnectDiscord: function() {
            document.forms['disconnect_discord'].submit();
        },
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
                currentPassword: this.currentPassword,
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
            this.loading = true;
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
                })
                .then(() => {
                    this.loading = false;
                });
        },
        toggleError: function(val) {
            this.disabled = val;
        },
        clearHistoryData: function() {
            if (window.history.replaceState) {
                window.history.replaceState({}, '', window.location.href);
            }
        },
        goToTFA: function(needPhone, disable2fa) {
            if (disable2fa) {
                this.goToPage(this.$routing.generate('two_factor_auth_disable'));
                return;
            }

            if (!this.hasPhoneVerified && needPhone) {
                this.addPhoneModalVisible = true;
                return;
            }

            this.goToPage(this.$routing.generate('two_factor_auth'));
        },
        onPhoneVerified() {
            this.closeAddPhoneModal();
            this.goToPage(this.$routing.generate('two_factor_auth'));
        },
        onEmailChanged() {
            this.changeEmailModalVisible = false;
        },
        closeAddPhoneModal: function() {
            this.addPhoneModalVisible = false;
        },
        showChangeEmailModal() {
            this.changeEmailModalVisible = true;
        },
    },
    store,
});
