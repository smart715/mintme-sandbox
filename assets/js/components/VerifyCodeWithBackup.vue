<template>
    <div>
        <verify-code
            v-if="!showBackupInput"
            :code-length="codeLength"
            :disabled="disabled"
            ref="verifyCode"
            @code-entered="onVerifyCodeEntered"
        />
        <template v-else>
            <input
                type="text"
                class="form-control"
                ref="backupCodeInput"
                :disabled="disabled"
                v-model.trim="backupCode"
            />
        </template>
        <div :class="linkContainerClass">
            <a href="#" @click.prevent="toggleBackupMode">{{ toggleLinkText }}</a>
        </div>
    </div>
</template>

<script>
import VerifyCode from './VerifyCode';

export const BACKUP_CODE_SIZE = 12;

export default {
    name: 'VerifyCodeWithBackup',
    components: {
        VerifyCode,
    },
    props: {
        codeLength: {
            type: Number,
            default: 6,
        },
        disabled: Boolean,
    },
    data() {
        return {
            showBackupInput: false,
            backupCode: '',
        };
    },
    computed: {
        toggleLinkText() {
            return this.showBackupInput
                ? this.$t('page.login_2fa.backup_link.authentication_code')
                : this.$t('page.login_2fa.backup_link.backup_code');
        },
        linkContainerClass() {
            return this.showBackupInput
                ? 'text-right small mx-2'
                : 'text-right small px-4 px-sm-4 mt-1 mx-5 mx-sm-1';
        },
    },
    watch: {
        backupCode() {
            if (this.backupCode && this.backupCode.length === BACKUP_CODE_SIZE) {
                this.onVerifyCodeEntered(this.backupCode);
            }
        },
    },
    methods: {
        toggleBackupMode() {
            if (this.disabled) {
                return;
            }

            this.backupCode = '';
            this.showBackupInput = !this.showBackupInput;

            this.$nextTick(() => {
                const ref = this.showBackupInput ? this.$refs['backupCodeInput'] : this.$refs['verifyCode'];

                if (ref) {
                    ref.focus();
                }
            });
        },
        onVerifyCodeEntered(code) {
            this.$emit('code-entered', code);
        },
    },
};
</script>
