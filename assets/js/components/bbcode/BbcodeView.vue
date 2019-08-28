<template>
    <div>
        <p v-html="parsedValue"></p>
    </div>
</template>

<script>
import bbob from '@bbob/core';
import {render} from '@bbob/html';
import presetHTML5 from '@bbob/preset-html5';

export default {
    name: 'BbcodeView',
    props: {
        value: String,
    },
    computed: {
        parsedValue: function() {
            if (null === this.value) return '';

            let value = this.value
                // xss protection
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            return bbob(presetHTML5())
                .process(value, {render})
                .html
                .replace(/<img src="/g, '<img style="max-width: 100%;" src="')
                .replace(/<a href="(http(s)?:\/\/)?/g, '<a rel="nofollow" target="_blank" href="https://');
        },
    },
};
</script>
