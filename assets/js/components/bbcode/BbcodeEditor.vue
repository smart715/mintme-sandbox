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
        this.$nextTick(() => {
            this.$el.addEventListener('input', this.resizeTextArea);
            this.$el.dispatchEvent(new Event('input'));
        });
    },
    watch: {
        value: function(val) {
            this.newValue = val;
        },
    },
    methods: {
        resizeTextArea: function(event) {
            if (event.target.style !== null) {
                event.target.style.height = 'auto';
                event.target.style.height = `${event.target.scrollHeight}px`;
            }
        },
        onChange: function(event) {
            this.newValue = event.target.value;
            this.$emit('change', this.newValue);
        },
        onInput: function(event) {
            this.newValue = event.target.value;
            this.$emit('input', this.newValue);
        },
    },
    beforeDestroy() {
        this.$el.removeEventListener('input', this.resizeTextArea);
    },
};
</script>
