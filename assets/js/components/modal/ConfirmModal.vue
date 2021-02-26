<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div v-if="showImage">
                    <img src="../../../img/are-you-sure.png"/>
                </div>
                <slot>
                    <p class="text-white modal-title pt-2 text-uppercase">
                        {{ $t('confirm_modal.body') }}
                    </p>
                </slot>
                <div class="pt-2">
                    <button
                        class="btn btn-primary"
                        :tabindex="9"
                        @click="onConfirm"
                        :disabled="buttonDisabled"
                    >
                        <slot name="confirm">{{ $t(modalConfirm) }}</slot>
                    </button>
                    <button
                        v-if="showCancelButton"
                        class="btn-cancel pl-3 bg-transparent"
                        :tabindex="10"
                        @click="onCancel">
                        <slot name="cancel">{{ $t('confirm_modal.cancel') }}</slot>
                    </button>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';

export default {
    name: 'ConfirmModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        showCancelButton: {
            type: Boolean,
            default: true,
        },
        showImage: {
            type: Boolean,
            default: true,
        },
        buttonDisabled: {
            type: Boolean,
            default: false,
        },
        modelConfirmProp: String,
    },
    computed: {
        modalConfirm: function() {
            return this.modelConfirmProp ? this.modelConfirmProp : 'confirm_modal.confirm';
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onConfirm: function() {
            this.closeModal();
            this.$emit('confirm');
        },
        onCancel: function(event) {
            event.preventDefault();
            this.closeModal();
            this.$emit('cancel');
        },
    },
};
</script>
