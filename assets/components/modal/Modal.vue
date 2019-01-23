<template>
    <b-modal :visible="visible" :size="size" @hidden="closeModal" hide-footer>
        <div slot="modal-header">
            <a class="modal-close float-right pr-2 c-pointer" @click="closeModal()">
                &times;
            </a>
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
    },
    methods: {
        closeModal: function() {
            this.$emit('close');
        },
    },
};
</script>

