<template>
    <b-modal
        :visible="visible"
        :size="size"
        :body-class="paddingClass"
        @hidden="closeModal"
        hide-footer
        :no-close-on-backdrop="noClose"
        :no-close-on-esc="noClose"
        :modal-class="{'modal-embeded': embeded}"
        :no-fade="embeded"
    >
        <div slot="modal-header" class="d-flex flex-nowrap justify-content-between">
            <span class="text-truncate d-block flex-grow-1 modal-title pl-4">
                <slot name="header"></slot>
            </span>
            <slot name="close">
                <a class="modal-close px-2 c-pointer float-right" @click="closeModal()">
                    &times;
                </a>
            </slot>
        </div>
        <div
            class="modal-body"
            :class="paddingClass"
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
        visible: Boolean,
        size: String,
        noClose: Boolean,
        withoutPadding: {type: Boolean, default: false},
        embeded: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        paddingClass: function() {
            return this.withoutPadding ? 'm-0 p-0': '';
        },
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

