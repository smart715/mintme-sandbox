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
                delay: [100, 1500],
            },
        };
    },
    methods: {
        onCopy: function(e) {
            this.tooltipMessage = 'Copied!';
            this.hideTooltip();
        },
        onError: function(e) {
            this.tooltipMessage = 'Press Ctrl+C to copy';
            this.hideTooltip();
        },
        hideTooltip: function() {
            if (this.$el._tippy != undefined) {
                setTimeout(()=> this.$el._tippy.hide(), 1500);
            }
        },
    },
};
</script>
