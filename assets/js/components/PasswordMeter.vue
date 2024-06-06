<template>
    <div class="form-control-container">
        <slot></slot>
        <div class="pwdmeter">
            <meter max="5" :value="pwdScore"></meter>
        </div>
        <div class="postfix-icon-container d-flex align-items-center">
            <slot v-if="!checkingDuplicate" name="postfix-icon"></slot>
            <template v-else>
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </template>
        </div>
        <div class="assistive d-flex px-0">
            <div class="errors flex-1 min-height-50">
                <div v-html="textStrengthSecurity"></div>
            </div>
        </div>
    </div>
</template>

<script>
import zxcvbn from 'zxcvbn';
import {CheckPasswordMixin} from '../mixins/';
import {API_TIMEOUT} from '../utils/constants';

export default {
    name: 'passwordmeter',
    props: {
        password: String,
        isForgotPassword: Boolean,
        token: String,
        isResetPassword: Boolean,
        currentPassword: String,
        showCurrentPasswordError: Boolean,
    },
    mixins: [CheckPasswordMixin],
    data: function() {
        return {
            pwdScore: 0,
            strengthText: 0,
            duplicateError: false,
            checkingDuplicate: false,
        };
    },
    computed: {
        textStrengthSecurity: function() {
            if (1 === this.strengthText) {
                return this.$t('passwordmeter.strength_1');
            }

            if (2 === this.strengthText) {
                return this.$t('passwordmeter.strength_2');
            }

            if (3 === this.strengthText) {
                return this.$t('passwordmeter.strength_3');
            }

            if (4 === this.strengthText) {
                return this.$t('passwordmeter.strength_4');
            }

            if (!this.checkingDuplicate && this.duplicateError) {
                return this.$t('passwordmeter.duplicate');
            }

            return '';
        },
        isPasswordDuplicate: function() {
            return this.password === this.currentPassword;
        },
        isPasswordInvalid: function() {
            return this.duplicateError || this.checkingDuplicate || !!this.strengthText;
        },
    },
    methods: {
        passwordEqualToSavedPassword: async function() {
            this.duplicateError = await this.isPasswordEqualToSavedPassword(this.password, this.token);
            this.checkingDuplicate = false;
        },
    },
    watch: {
        password: function(val) {
            this.checkingDuplicate = false;
            this.duplicateError = false;
            const result = zxcvbn(val);

            if ('' !== val) {
                result.score = result.score + 1;
            }

            if (7 >= val.length && 4 <= result.score) {
                result.score = 3;
            }

            this.pwdScore = result.score;

            if (7 >= val.length && 1 <= result.score) {
                this.strengthText = 1;
            } else if (8 <= val.length && 5 >= result.score) {
                let number = 0;
                let uppercase = 0;
                let lowercase = 0;

                if (/\d/.test(val)) {
                    number = 1;
                }

                if (/[a-z]/.test(val)) {
                    lowercase = 1;
                }

                if (/[A-Z]/.test(val)) {
                    uppercase = 1;
                }

                if (3 !== number + uppercase + lowercase) {
                    this.strengthText = 2;
                } else if (72 < val.length) {
                    this.strengthText = 3;
                } else if (/\s/.test(val)) {
                    this.strengthText = 4;
                } else {
                    this.strengthText = 0;
                }
            } else {
                if (/\s/.test(val)) {
                    this.strengthText = 4;
                } else {
                    this.strengthText = 0;
                }
            }

            if (
                0 === this.strengthText
                && 0 < val.length
                && this.isForgotPassword
                && 0 < this.token.length
            ) {
                this.checkingDuplicate = true;
                return setTimeout(this.passwordEqualToSavedPassword, API_TIMEOUT);
            }

            if (
                0 === this.strengthText
                && 0 < val.length
                && this.isResetPassword
                && !this.showCurrentPasswordError
                && 0 < this.currentPassword.length
            ) {
                this.duplicateError = this.isPasswordDuplicate;
            }
        },
        strengthText: function() {
            this.$emit('toggle-error', this.isPasswordInvalid);
        },
        checkingDuplicate: function() {
            this.$emit('toggle-error', this.isPasswordInvalid);
        },
        duplicateError: function() {
            this.$emit('toggle-error', this.isPasswordInvalid);
        },
        showCurrentPasswordError: function(val) {
            this.checkingDuplicate = false;
            this.duplicateError = false;
            this.$emit('toggle-error', !!val);
        },
        currentPassword: function() {
            this.checkingDuplicate = false;
            this.duplicateError = this.isPasswordDuplicate;
        },
    },
};
</script>
