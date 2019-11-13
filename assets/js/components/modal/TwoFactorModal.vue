<template>
    <modal
        :visible="visible"
        :no-close="noClose"
        @close="closeModal">
        <template slot="close"> &nbsp; </template>
        <template slot="body">
            <div class="text-center">
                <div class="col-12 pb-3">
                    <label for="twofactor" class="d-block text-left">
                        {{ label }}
                    </label>
                    <input
                        v-model="code"
                        type="text"
                        id="twofactor"
                        class="form-control">
                </div>
                <div class="col-12 pt-2 text-center">
                    <button
                        class="btn btn-primary"
                        @click="onVerify">
                        Verify Code
                    </button>
                    <span
                        class="btn-cancel pl-3 c-pointer"
                        @click="closeModal">
                        <slot name="cancel">Cancel</slot>
                    </span>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {required} from 'vuelidate/lib/validators';
import {NotificationMixin} from '../../mixins';

export default {
    name: 'TwoFactorModal',
    mixins: [NotificationMixin];
    components: {
        Modal,
    },
    props: {
        noClose: {type: Boolean, default: true},
        twofa: Boolean,
        visible: Boolean,
    },
    data() {
        return {
            code: '',
            label: this.twofa ? 'Two Factor Authentication Code:' : 'Email Verification Code:',
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
                this.notifyError('Code is required');
                return;
            }
            this.$emit('verify', this.code);
        },
    },
    validations() {
        return {
            code: {
                required,
            },
        };
    },
};
</script>

