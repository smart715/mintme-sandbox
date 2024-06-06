<template>
    <b-dropdown-item
        ref="dropdownItem"
        :value="value"
        @click="$emit('click', $event)"
        :class="itemClass"
        :href="link"
    >
        <slot></slot>
    </b-dropdown-item>
</template>

<script>
import {BDropdownItem} from 'bootstrap-vue';

export default {
    name: 'm-dropdown-item',
    components: {
        BDropdownItem,
    },
    props: {
        value: [Number, String, Array, Object],
        active: Boolean,
        link: String,
    },
    computed: {
        itemClass: function() {
            const classList = [];

            if (this.active) {
                classList.push('active');
            }

            return classList;
        },
    },
};
</script>
