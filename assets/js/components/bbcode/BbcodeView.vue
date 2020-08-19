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
import {sanitizeOptions, ourDomains} from '../../utils/constants.js';

if (typeof Vue !== 'undefined') {
  Vue.use(VueSanitize, sanitizeOptions);
}

export default {
    name: 'BbcodeView',
    props: {
        value: String,
    },
    computed: {
        parsedValue: function() {
            if (null === this.value) return '';

            const value = this.value
                .replace(/\[\/yt]/g, '"][/iframe][/div]')
                .replace(/\[yt]/g,
                    '[div class="embed-responsive embed-responsive-16by9"]' +
                    '[iframe class="embed_responsive_item" frameborder="0" allow = "accelerometer; autoplay; ' +
                    'encrypted-media;' +
                    ' gyroscope; picture-in-picture"' +
                    ' allowfullscreen src="https://www.youtube.com/embed/'
                );

            const html = bbob(presetHTML5())
                .process(value, {render})
                .html
                .replace(/<img src="/g, '<img style="max-width: 100%;" src="')
                .replace(/<li>/g, '<li><span class="bbcode-span-list-item">')
                .replace(/<\/li>/g, '</span></li>');

            return this.$sanitize(this.checkBbcodeLinks(html));
        },
    },
    methods: {
        checkBbcodeLinks: function(text) {
            let ourDomainsRegex = new RegExp(ourDomains.join('|'));
            let div = document.createElement('div');
            div.innerHTML = text;
            let links = div.getElementsByTagName('a');

            for (let link of links) {
                let linkHref = link.outerHTML;

                if (!ourDomainsRegex.test(linkHref)) {
                    link.rel = 'noreferrer';
                    link.target = '_blank';
                } else {
                    link.removeAttribute('rel');
                    link.removeAttribute('target');
                }

                let linkHrefChanged = link.outerHTML;

                if (linkHref !== linkHrefChanged) {
                    text = text.replace(linkHref, linkHrefChanged);
                }
            }

            return text;
        },
    },
};
</script>
