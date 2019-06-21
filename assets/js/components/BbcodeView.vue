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
    data() {
        return {
            parser: null,
        };
    },
    computed: {
        parsedDescription: function() {
            if (null === this.description) return '';

            return bbob(presetHTML5()).process(this.description, {render}).html;
        },
    },
};
</script>
