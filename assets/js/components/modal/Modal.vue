<template>
    <b-modal
        :id="id"
        :visible="visible"
        :size="size"
        :body-class="bodyClasses"
        @hidden="closeModal"
        hide-footer
        :no-close-on-backdrop="noClose"
        :no-close-on-esc="noClose"
        :modal-class="{'modal-embeded': embeded}"
        :dialog-class="dialogClass"
        :no-fade="embeded"
    >
        <div slot="modal-header" class="d-flex flex-nowrap align-items-center">
            <!-- to keep same space on left and right -->
            <div class="invisible pointer-events-none flex-grow-0">
                <slot name="close">
                    <a class="modal-close px-3 c-pointer float-right">
                        &times;
                    </a>
                </slot>
            </div>
            <div class="text-truncate d-flex align-items-center justify-content-center flex-fill modal-title">
                <slot name="header"></slot>
            </div>
            <slot v-if="enableCloseBtn" name="close">
                <a class="modal-close modal-close-visible px-3 c-pointer" @click="closeModal()">
                    &times;
                </a>
            </slot>
        </div>
        <div
            class="modal-body"
            :class="bodyClasses"
        >
            <slot name="body"></slot>
        </div>
    </b-modal>
</template>

<script>
import {BModal} from 'bootstrap-vue';

export default {
    name: 'Modal',
    components: {
        BModal,
    },
    props: {
        id: String,
        visible: Boolean,
        size: String,
        noClose: Boolean,
        withoutPadding: {type: Boolean, default: false},
        embeded: {
            type: Boolean,
            default: false,
        },
        dialogClass: [String, Object, Array],
        enableCloseBtn: {
            type: Boolean,
            default: true,
        },
        bodyClass: {type: String, default: ''},
    },
    computed: {
        bodyClasses: function() {
            const padding = this.withoutPadding ? 'm-0 p-0' : '';

            return this.bodyClass ? this.bodyClass + ' ' + padding : padding;
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

