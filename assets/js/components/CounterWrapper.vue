<template>
    <div class="counter-wrapper">
        <slot></slot>
        <span
            v-if="showCounter"
            class="badge badge-pill badge-danger p-2 mb-3 counter-icon"
            :class="{
                'block' : block,
                'custom-icon position-absolute' : icon,
            }"
        >
            {{ countHumanized }}
        </span>
    </div>
</template>

<script>

export default {
    name: 'CounterWrapper',
    props: {
        count: Number,
        block: Boolean,
        icon: {
            type: Boolean,
            default: true,
        },
    },
    computed: {
        countHumanized: function() {
            return 99 < this.count
                ? '99+'
                : this.count;
        },
        showCounter() {
            return 0 < this.count;
        },
    },
};
</script>
