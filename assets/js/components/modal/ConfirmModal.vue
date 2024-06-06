<template>
    <modal
        :visible="visible"
        :embeded="embeded"
        :size="size"
        :dialog-class="dialogClass"
        @close="closeModal"
    >
        <template v-slot:header>
            <div
                class="highlight font-size-3 font-weight-bold text-center"
                v-html="modalTitle"
            ></div>
        </template>
        <template slot="body">
            <div class="text-center word-break">
                <div v-if="isDeleteType" class="text-primary mb-2">
                    <font-awesome-icon
                        :icon="['fas', 'trash']"
                        size="6x"
                    />
                </div>
                <div v-if="isWarningType" class="mb-3">
                    <img src="../../../img/exclamation-triangle.svg" class="confirm-modal-image" />
                </div>
                <div v-if="showImage">
                    <img src="../../../img/are-you-sure.png" />
                </div>
                <slot>
                    <p class="text-white modal-title text-break pt-2 text-uppercase">
                        {{ $t('confirm_modal.body') }}
                    </p>
                </slot>
                <div class="pt-2">
                    <m-button
                        v-if="showConfirmButton"
                        type="primary"
                        :tabindex="9"
                        @click="onConfirm"
                        :disabled="buttonDisabled"
                        :loading="submitting"
                    >
                        <slot name="confirm">{{ $t(modalConfirm) }}</slot>
                    </m-button>
                    <button
                        v-if="showCancelButton"
                        class="btn-cancel pl-3 bg-transparent"
                        :tabindex="10"
                        @click="onCancel">
                        <slot name="cancel">{{ $t('confirm_modal.cancel') }}</slot>
                    </button>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from './Modal.vue';
import {MButton} from '../UI';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTrash} from '@fortawesome/free-solid-svg-icons';

library.add(faTrash);

export default {
    name: 'ConfirmModal',
    components: {
        Modal,
        MButton,
        FontAwesomeIcon,
    },
    props: {
        visible: Boolean,
        showConfirmButton: {
            type: Boolean,
            default: true,
        },
        showCancelButton: {
            type: Boolean,
            default: true,
        },
        showImage: {
            type: Boolean,
            default: true,
        },
        buttonDisabled: {
            type: Boolean,
            default: false,
        },
        modelConfirmProp: String,
        embeded: {
            type: Boolean,
            default: false,
        },
        submitting: Boolean,
        closeOnConfirm: {
            type: Boolean,
            default: true,
        },
        type: String,
        title: String,
        noTitle: {
            type: Boolean,
            default: false,
        },
        size: {
            type: String,
            default: 'lg',
        },
        dialogClass: String,
    },
    computed: {
        modalConfirm() {
            return this.modelConfirmProp ? this.modelConfirmProp : 'confirm_modal.confirm';
        },
        isDeleteType() {
            return 'delete' === this.type;
        },
        isWarningType() {
            return 'warning' === this.type;
        },
        modalTitle() {
            if (this.noTitle) {
                return '';
            }

            return this.title || this.$t('confirm_modal.header');
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
        onConfirm: function() {
            if (this.closeOnConfirm) {
                this.closeModal();
            }

            this.$emit('confirm');
        },
        onCancel: function(event) {
            event.preventDefault();
            this.closeModal();
            this.$emit('cancel');
        },
    },
};
</script>
