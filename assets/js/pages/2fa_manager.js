import BackupCodesModal from '../components/modal/BackupCodesModal';
import VerifyCode from '../components/VerifyCode';
import VerifyCodeWithBackup from '../components/VerifyCodeWithBackup';
import i18n from '../utils/i18n/i18n';
import store from '../storage';

new Vue({
    el: '#tfa-manager',
    i18n,
    components: {
        BackupCodesModal,
        VerifyCode,
        VerifyCodeWithBackup,
    },
    data() {
        return {
            backupCodesModalEnable: false,
            backupCodesModalNoClose: true,
            loading: false,
        };
    },
    methods: {
        onBackupCodesModalClose: function() {
            this.backupCodesModalEnable = false;
        },
        showModal: function() {
            this.backupCodesModalEnable = true;
        },
        downloadCodes: function() {
            this.showModal();
        },
        onVerifyCodeEntered(code) {
            this.$refs['code_input'].value = code;
            this.loading = true;

            const form = document.getElementById('form_code');
            form.submit();
        },
    },
    store,
});
