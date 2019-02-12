<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="mt-2">
                    <img src="../../img/are-you-sure.png"/>
                </div>
                <slot>
                    <p class="text-white modal-title pt-2 pb-4 text-uppercase">
                        ARE YOU SURE?
                    </p>
                </slot>
                <button
                    class="btn btn-primary"
                    @click="onConfirm">
                    <slot name="confirm">CONFIRM</slot>
                </button>
                <a
                    href="#"
                    class="ml-3"
                    @click="onCancel">
                    <slot name="cancel">CANCEL</slot>
                </a>
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
        onCancel: function() {
            this.closeModal();
            this.$emit('cancel');
        },
    },
};
</script>
