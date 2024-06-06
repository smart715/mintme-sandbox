import {library} from '@fortawesome/fontawesome-svg-core';
import {faEye} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faEye);

export default {
    components: {
        FontAwesomeIcon,
    },
    methods: {
        togglePassword: function() {
            this.isPassVisible ? this.hidePassword() : this.showPassword();
        },
        showPassword: function() {
            this.passwordInput.type = 'password';
            this.eyeIcon.className = 'show-password';
            this.isPassVisible = true;
        },
        hidePassword: function() {
            this.passwordInput.type = 'text';
            this.eyeIcon.className = 'show-password-active';
            this.isPassVisible = false;
        },
    },
};
