<template>
    <modal
        :visible="visible"
        :no-close="noClose"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="col-12 pb-3">
                    <label for="twofactor" class="d-block text-left">
                        {{ $t(label) }}
                    </label>
                    <input
                        v-if="!twofa"
                        v-model="code"
                        type="text"
                        id="twofactor"
                        class="form-control"
                    >
                    <verify-code v-else @code-entered="onVerifyCodeEntered" />
                </div>
                <div class="col-12 pt-2 text-center">
                    <button
                        class="btn btn-primary"
                        v-if="!validCode"
                        :disabled="validCode"
                        @click="onVerify"
                    >
                        {{ $t('2fa_modal.submit') }}
                    </button>
                    <span
                        v-if="!validCode"
                        class="btn-cancel pl-3 c-pointer"
                        @click="closeModal"
                    >
                        <slot name="cancel">{{ $t('2fa_modal.cancel') }}</slot>
                    </span>
                    <font-awesome-icon v-if="validCode" icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {required} from 'vuelidate/lib/validators';
import {NotificationMixin} from '../../mixins';
import {twoFACode} from '../../utils/constants';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VerifyCode from '../VerifyCode';

export default {
    name: 'TwoFactorModal',
    mixins: [NotificationMixin],
    components: {
        Modal,
        FontAwesomeIcon,
        VerifyCode,
    },
    props: {
        noClose: {type: Boolean, default: true},
        twofa: Boolean,
        loading: {type: Boolean, default: false},
        visible: Boolean,
    },
    data() {
        return {
            code: '',
            emailCodeLength: 64,
            label: this.twofa ? '2fa_modal.label.2fa' : '2fa_modal.label.email',
        };
    },
    methods: {
        closeModal: function() {
            this.code = '';
            this.$emit('close');
        },
        onVerify: function() {
            this.$v.$touch();
            if (this.$v.$error) {
                this.notifyError(this.$t('2fa_modal.require'));
                return;
            }
            this.$emit('verify', this.code);
        },
        checkCode: function(code) {
            return this.twofa
                ? twoFACode(code)
                : code.length === this.emailCodeLength;
        },
        onVerifyCodeEntered: function(code) {
            this.code = code;
        },
    },
    validations() {
        return {
            code: {
                required,
            },
        };
    },
    computed: {
        validCode: function() {
            return this.loading && this.code
                ? this.checkCode(this.code)
                : false;
        },
    },
    watch: {
        code: function(val) {
            if (val && this.checkCode(val)) {
                this.onVerify();
            }
        },
    },
};
</script>

