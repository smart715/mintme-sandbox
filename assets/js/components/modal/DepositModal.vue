<template>
    <modal
        id="modal"
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3 class="modal-title">DEPOSIT</h3>
                <div class="col-12 pt-2">
                    <code class="wallet-code" id="walletaddress">
                        <span>
                            {{ address }}
                        </span>
                        <copy-link :content-to-copy="address" class="c-pointer">
                            <font-awesome-icon :icon="['far', 'copy']">
                            </font-awesome-icon>
                        </copy-link>
                    </code>
                    <div class="clearfix"></div>
                    <b-row>
                        <b-col>
                            <p class="text-center mt-2">
                                {{ description }}
                            </p>
                        </b-col>
                    </b-row>
                    <b-row>
                        <b-col v-if="min" cols="auto" class="text-left">Minimal value: {{ min }} {{ currency }}</b-col>
                        <b-col v-if="fee" class="text-right">Fee: {{ fee }} {{ currency }}</b-col>
                    </b-row>
                </div>
                <div class="pt-2 text-center">
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
        currency: String,
        min: String,
        fee: String,
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

