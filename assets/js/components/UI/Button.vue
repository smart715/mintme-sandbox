<template>
    <button class="btn position-relative" :class="btnClass" @click="onClick">
        <div class="content d-flex align-items-center justify-content-center flex-fill" :class="btnContentClass">
            <slot name="prefix"></slot>
            <slot name="default"></slot>
        </div>
        <div v-if="loading" class="d-flex position-absolute top-50 left-50 translate-middle">
            <div class="spinner-border spinner-border-sm" role="status"></div>
        </div>
    </button>
</template>

<script>
export default {
    name: 'm-button',
    props: {
        type: {
            type: String,
            default: null,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        wide: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        btnClass: function() {
            const btnClass = {
                'disabled': this.disabled || this.loading,
                'btn-loading': this.loading,
                'btn-wide': this.wide,
            };

            if (this.type) {
                btnClass['btn-' + this.type] = true;
            }

            return btnClass;
        },
        btnContentClass: function() {
            return this.loading ? 'opacity-0' : '';
        },
    },
    methods: {
        onClick: function(event) {
            if (!this.disabled && !this.loading) {
                this.$emit('click', event);
            }
        },
    },
};
</script>
