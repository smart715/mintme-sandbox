<template>
    <b-modal :visible="visible" :size="size" @hidden="closeModal" hide-footer>
        <div slot="modal-header">
            <a class="modal-close float-right pr-2" @click="closeModal()">
                <font-awesome-icon icon="times" slot='icon'/>
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

