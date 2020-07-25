<template>
    <div>
        <p v-html="parsedValue" class="bbcode-content"></p>
    </div>
</template>

<script>
import bbob from '@bbob/core';
import {render} from '@bbob/html';
import presetHTML5 from '@bbob/preset-html5';
import VueSanitize from 'vue-sanitize';
import {sanitizeOptions} from '../../utils/constants.js';

Vue.use(VueSanitize, sanitizeOptions);

export default {
    name: 'BbcodeView',
    props: {
        value: String,
    },
    computed: {
        parsedValue: function() {
            if (null === this.value) return '';

            const html = bbob(presetHTML5())
                .process(this.value, {render})
                .html
                .replace(/<img src="/g, '<img style="max-width: 100%;" src="')
                .replace(/<li>/g, '<li><span class="bbcode-span-list-item">')
                .replace(/<\/li>/g, '</span></li>')
                .replace(/<a href="(http(s)?:\/\/)?/g, '<a rel="nofollow" target="_blank" href="https://');
            return this.$sanitize(html);
        },
    },
};
</script>
