import Passwordmeter from './components/PasswordMeter';
import Guide from './components/Guide';
import {minLength} from 'vuelidate/lib/validators';
import {nickname} from './utils/constants';

new Vue({
    el: '#register',
    components: {
        Passwordmeter,
        Guide,
    },
    data() {
      return {
          nickname: '',
          password: '',
          disabled: false,
          passwordInput: null,
          isPass: true,
          eyeIcon: null,
      };
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
    mounted() {
        this.passwordInput = document.getElementById('fos_user_registration_form_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
        this.nickname = this.$refs.nickname.getAttribute('value');
    },
    validations: {
        nickname: {
            helpers: nickname,
            minLength: minLength(2),
        },
    },
});
