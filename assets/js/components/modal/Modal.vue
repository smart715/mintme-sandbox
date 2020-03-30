<template>
    <b-modal
        :visible="visible"
        :size="size"
        :body-class="paddingClass"
        @hidden="closeModal"
        hide-footer
        :no-close-on-backdrop="noClose"
        :no-close-on-esc="noClose"
    >
        <div slot="modal-header" class="d-flex flex-nowrap justify-content-between">
            <span class="d-inline-block flex-grow-1 modal-title p-2 pl-4">
                <slot name="header"></slot>
            </span>
            <slot name="close">
                <a class="modal-close px-2 c-pointer" @click="closeModal()">
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
export default {
    name: 'Modal',
    props: {
        visible: Boolean,
        size: String,
        noClose: Boolean,
        withoutPadding: {type: Boolean, default: false},
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

