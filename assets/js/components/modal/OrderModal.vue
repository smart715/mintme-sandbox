<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="mt-2">
                    <img v-if="this.type" src="../../../img/order-created.png"/>
                    <img v-if="!this.type" src="../../../img/order-failed.png"/>
                </div>
                <p class="text-white modal-title pt-2 pb-4 text-uppercase">{{ title }}</p>
                <button
                    class="btn btn-primary"
                    @click="closeModal()">
                    Ok
                </button>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
export default {
    name: 'OrderModal',
    components: {
        Modal,
    },
    props: {
        type: Boolean,
        visible: Boolean,
    },
    computed: {
        title: function() {
            return (this.type) ? 'Order Created' : 'Insufficient Balance';
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

