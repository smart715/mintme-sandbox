<template>
    <div>
        <p v-html="parsedDescription"></p>
    </div>
</template>

<script>
import bbob from '@bbob/core';
import {render} from '@bbob/html';
import presetHTML5 from '@bbob/preset-html5';

export default {
    name: 'BbcodeView',
    props: {
        description: String,
    },
    computed: {
        parsedDescription: function() {
            if (null === this.description) return '';

            return bbob(presetHTML5())
                .process(this.description, {render})
                .html
                .replace(/<img src="/g, '<img style="max-width: 100%;" src="')
                .replace(/<a href="(http(s)?:\/\/)?/g, '<a rel="nofollow" target="_blank" href="https://');
        },
    },
};
</script>
