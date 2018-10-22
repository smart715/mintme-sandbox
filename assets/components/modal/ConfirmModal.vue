<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="mb-5">
                    <img src="../../img/are-you-sure.png"/>
                    <slot><h3>Are you sure that you want to remove {{ tokenName }}
                        with amount {{ amount }} and price {{ price }}</h3></slot>
                </div>
                <button
                    class="btn btn-primary"
                    @click="onConfirm">
                    <slot name="confirm">CONFIRM</slot>
                </button>
                <a
                    href="#"
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
        tokenName: String,
        amount: Number,
        price: Number,
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

