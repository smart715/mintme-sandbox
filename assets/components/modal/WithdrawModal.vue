<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body" v-if="formLoaded">
            <div class="text-center">
                <h3>WITHDRAW(NAME)</h3>
                <div class="col-12 pt-3">
                    <label for="address" class="d-block text-left">
                        Address:
                    </label>
                    <input
                        type="text"
                        id="address"
                        class="form-control">
                </div>
                <div class="col-12 pt-3">
                    <label for="amount"  class="d-block text-left">
                        Amount (balance):
                    </label>
                    <div class="text-right">
                        <input type="text" class="form-control text-left input-custom-padding">
                        <button
                            class="btn btn-primary btn-input"
                            type="button">
                            All
                        </button>
                    </div>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        Amount WEB:
                    </label>
                    <span class="float-right">{{ amountWEB }}</span>
                </div>
                <div class="pt-3">
                    <button
                        class="btn btn-primary"
                        @click="onWithdraw">
                        WITHDRAW
                    </button>
                    <a
                        href="#"
                        class="ml-3"
                        @click="onCancel">
                        <slot name="cancel">CANCEL</slot>
                    </a>
                </div>
            </div>
        </template>
        <template slot="body" v-else>
            <div class="row mb-3">
                <div class="col text-center">
                    <font-awesome-layers class="fa-3x">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width  />
                    </font-awesome-layers>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
export default {
    name: 'WithdrawModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        amountWEB: Number,
    },
    data() {
        return {
            formLoaded: false,
        };
    },
    mounted: function() {
        this.formLoaded = true;
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onWithdraw: function() {
            this.closeModal();
            this.$emit('withdraw');
        },
        onCancel: function() {
            this.closeModal();
            this.$emit('cancel');
        },
    },
};
</script>

