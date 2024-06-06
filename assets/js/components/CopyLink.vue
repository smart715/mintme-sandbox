<template>
    <a
        v-clipboard:copy="contentToCopy"
        @click.prevent=""
        :title="tooltipMessage"
        v-tippy="tooltipOptions"
        v-clipboard:success="onCopy"
        v-clipboard:error="onError">
        <slot>{{ $t('copy_link.label') }}</slot>
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
                followCursor: 'initial',
                delay: [100, 1500],
            },
        };
    },
    methods: {
        onCopy: function(e) {
            this.tooltipMessage = this.$t('copy_link.copied');
            this.hideTooltip();
        },
        onError: function(e) {
            this.tooltipMessage = this.$t('copy_link.press_ctrl_c');
            this.hideTooltip();
        },
        hideTooltip: function() {
            setTimeout(()=> {
                if (this.$el._tippy != undefined) {
                    this.$el._tippy.hide();
                }
            }, 1500);
        },
    },
};
</script>
