<template>
    <b-modal
        :visible="visible"
        :size="size"
        @hidden="closeModal"
        hide-footer
        :no-close-on-backdrop="noClose"
        :no-close-on-esc="noClose">
        <div slot="modal-header">
            <slot name="header"></slot>
            <slot name="close">
                <a class="modal-close float-right pr-2 c-pointer" @click="closeModal()">
                    &times;
                </a>
            </slot>
        </div>
        <div class="modal-body">
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
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

