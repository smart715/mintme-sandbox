import {TogglePassword} from '../mixins';
import i18n from '../utils/i18n/i18n';

export default {
    i18n,
    mixins: [TogglePassword],
    data() {
        return {
            password: '',
            passwordInput: null,
            isPassVisible: true,
            eyeIcon: null,
            changeEmailModalVisible: false,
        };
    },
    mounted() {
        this.passwordInput = this.$refs['password-input'];
        this.eyeIcon = this.$refs['eye-icon'];
    },
};
