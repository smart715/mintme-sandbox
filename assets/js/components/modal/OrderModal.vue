<template>
    <modal
        :visible="visible"
        :no-close="false"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <div class="mt-2">
                    <img v-if="this.type" src="../../../img/order-created.png"/>
                    <img v-if="!this.type" src="../../../img/order-failed.png"/>
                </div>
                <p class="text-white modal-title pt-2 pb-4 text-uppercase">{{ title|rebranding }}</p>
                <button
                    class="btn btn-primary"
                    @click="closeModal()">
                    {{ $t('order_modal.ok') }}
                </button>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {RebrandingFilterMixin} from '../../mixins/';

export default {
    name: 'OrderModal',
    mixins: [RebrandingFilterMixin],
    components: {
        Modal,
    },
    props: {
        type: Boolean,
        title: String,
        visible: Boolean,
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

