<template>
    <textarea
        class="custom-scrollbar"
        v-model="newValue"
        @change="onChange($event)"
        @input="onInput($event)"
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
        onChange: function(event) {
            this.newValue = event.target.value;
            this.$emit('change', this.newValue);
        },
        onInput: function(event) {
            this.newValue = event.target.value;
            this.$emit('input', this.newValue);
        },
    },
};
</script>
