<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :embeded="embeded"
            @close="closeModal">
            <template slot="body">
                <div class="text-center">
                    <div class="col-12 pt-2" v-html-sanitize="body"></div>
                    <div class="pt-2 text-center">
                        <button
                            class="btn btn-primary"
                            @click="closeModal"
                        >
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';

export default {
    name: 'AddPhoneAlertModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        message: String,
        embeded: {
            type: Boolean,
            default: false,
        },
        noClose: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        confirmText: function() {
            return this.embeded ? this.$t('page.reload') : this.$t('deposit_modal.ok');
        },
        body: function() {
            return this.message;
        },
    },
    methods: {
        closeModal: function() {
            if (this.embeded) {
                return location.reload();
            }
            this.$emit('close');
        },
    },
};
</script>
