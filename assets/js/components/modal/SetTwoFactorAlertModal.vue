<template>
    <div>
        <modal
            ref="modal"
            :visible="visible"
            :no-close="noClose"
            @close="$emit('close')"
        >
            <template slot="body">
                <div>
                    <div class="d-flex align-items-center justify-content-center">
                        {{ body }}
                    </div>
                </div>
                <div class="pt-2 d-flex align-items-center justify-content-center">
                    <button
                        class="btn btn-cancel"
                        @click="$emit('close')"
                    >
                        {{ $t('modal.set_two_factor_alert.confirm') }}
                    </button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';

export default {
    name: 'SetTwoFactorAlertModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        message: String,
        noClose: {
            type: Boolean,
            default: true,
        },
    },
    computed: {
        body() {
            return this.message;
        },
    },
};
</script>
