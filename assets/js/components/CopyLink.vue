<template>
    <a
        v-clipboard:copy="contentToCopy"
        :title="tooltipMessage"
        v-tippy="tooltipOptions"
        v-clipboard:success="onCopy"
        v-clipboard:error="onError">
        <slot>Copy to clipboard</slot>
    </a>
</template>

<script>
export default {
    name: 'CopyLink',
    props: {
        contentToCopy: String,
    },
    data() {
        return {
            tooltipMessage: '',
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'click',
                delay: [100, 2000],
            },
        };
    },
    methods: {
        onCopy: function(e) {
            this.tooltipMessage = 'Copied!';
        },
        onError: function(e) {
            this.tooltipMessage = 'Press Ctrl+C to copy';
        },
    },
};
</script>
