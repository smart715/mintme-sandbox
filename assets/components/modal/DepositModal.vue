<template>
    <modal
        id="modal"
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3>DEPOSIT</h3>
                <div class="col-12 pt-3">
                    <code class="wallet-code" id="walletaddress">
                        <span>
                            {{ address }}
                        </span>
                        <copy-link :content-to-copy="address">
                            <font-awesome-icon :icon="['far', 'copy']">
                            </font-awesome-icon>
                        </copy-link>
                    </code>
                    <div class="clearfix"></div>
                    <p class="text-center mt-3">
                        {{ description }}
                    </p>
                </div>
                <div class="pt-3">
                    <button
                        class="btn btn-primary"
                        @click="onSuccess">
                        OK
                    </button>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import CopyLink from '../CopyLink';

export default {
    name: 'DepositModal',
    components: {
        Modal,
        CopyLink,
    },
    props: {
        visible: Boolean,
        address: String,
        description: String,
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onSuccess: function() {
            this.closeModal();
            this.$emit('success');
        },
    },
};
</script>

