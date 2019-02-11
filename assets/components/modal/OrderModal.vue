<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3 class="font-weight-bold">{{ title }}</h3>
                <div class="my-5">
                    <img v-if="this.type" src="../../img/order-created.png"/>
                    <img v-if="!this.type" src="../../img/order-failed.png"/>
                </div>
                <button
                    class="btn btn-primary px-5"
                    @click="closeModal()">
                    OK
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
        modalTitle: String,
    },
    computed: {
        title: function() {
            return (this.type) ? 'Order Created' : this.modalTitle ? this.modalTitle: 'Order Failed';
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

