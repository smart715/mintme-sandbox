<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div>
                    <img src="../../../img/are-you-sure.png"/>
                </div>
                <slot>
                    <p class="text-white modal-title pt-2 text-uppercase">
                        ARE YOU SURE?
                    </p>
                </slot>
                <div class="pt-2">
                    <button
                        class="btn btn-primary"
                        @click="onConfirm">
                        <slot name="confirm">Confirm</slot>
                    </button>
                    <span
                        class="btn-cancel pl-3 c-pointer"
                        @click="onCancel">
                        <slot name="cancel">Cancel</slot>
                    </span>
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
