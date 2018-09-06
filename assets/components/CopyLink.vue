<template>
    <a  class="copy-link" :title="tooltipMessage" v-tippy="tooltipOptions">
        <slot>Copy to clipboard</slot>
    </a>
</template>

<script>
import ClipboardJS from 'clipboard';
import VueTippy from 'vue-tippy';
Vue.use(VueTippy);

let clipboard = new ClipboardJS('.copy-link');

export default {
    name: 'CopyLink',
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
    created: function() {
        clipboard.on('success', () => {
            this.tooltipMessage = 'Copied!';
        });
        clipboard.on('error', () => {
            this.tooltipMessage = 'Press Ctrl+C to copy';
        });
    },
};
</script>
