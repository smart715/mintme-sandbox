import VerifyCode from './components/VerifyCode';
import VerifyCodeWithBackup from './components/VerifyCodeWithBackup';
import i18n from './utils/i18n/i18n';

new Vue({
    el: '#check_code',
    i18n,
    components: {
        VerifyCode,
        VerifyCodeWithBackup,
    },
    data() {
        return {
            loading: false,
        };
    },
    methods: {
        onVerifyCodeEntered(code) {
            this.$refs['code_input'].value = code;
            this.loading = true;

            const form = document.getElementById('form_code');
            form.submit();
        },
    },
});
