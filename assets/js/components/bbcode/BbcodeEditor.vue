<template>
    <textarea
        v-model="newValue"
        @change="onEvent('change', $event)"
        @input="onEvent('input', $event)"
    ></textarea>
</template>

<script>
import {useMarkitup} from '../../utils';

export default {
    name: 'BbcodeEditor',
    props: {
        value: String,
    },
    data() {
        return {
            newValue: this.value,
        };
    },
    mounted: function() {
        useMarkitup('textarea');
    },
    watch: {
        value: function(val) {
            this.newValue = val;
        },
    },
    methods: {
        onEvent: function(type, event) {
            this.newValue = event.target.value;
            this.$emit(type, this.newValue);
        },
    },
};
</script>
