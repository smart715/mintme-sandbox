<template>
    <modal
        :loading="loading"
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="mb-5">
                    <img src="../../img/are-you-sure.png"/>
                    <slot><h3>ARE YOU SURE?</h3></slot>
                </div>
                <button
                    class="btn btn-primary"
                    @click="onConfirm">
                    <font-awesome-icon v-if="loading" icon="circle-notch" spin class="loading-spinner" fixed-width />
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
        loading: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onConfirm: function() {
            if (this.loading) {
                return;
            }
            this.$emit('loading', true);
            this.$emit('confirm');
        },
        onCancel: function() {
            this.closeModal();
            this.$emit('cancel');
        },
    },
};
</script>
