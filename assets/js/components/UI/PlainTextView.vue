<template>
    <div class="plain-text-content text-break text-justify my-2">
        <div v-html="parsedText" ref="textContainer"></div>
        <youtube-channels
            v-if="hasYoutubeChannels"
            :urls="youtubeChannels"
        />
    </div>
</template>

<script>

import VueSanitize from 'vue-sanitize';
import {sanitizeOptions} from '../../utils/constants.js';
import YoutubeChannels from '../token/youtube/YoutubeChannels';
import {
    REGEX_IMAGE,
    REGEX_URL,
    REGEX_YOUTUBE,
    REGEX_YOUTUBE_CHANNEL,
    REGEX_YOUTUBE_URL_ID,
} from '../../utils/regex';
import {addHtmlHashtagsToText} from '../../utils';

if ('undefined' !== typeof Vue) {
    Vue.use(VueSanitize, sanitizeOptions);
}

export default {
    name: 'PlainTextView',
    props: {
        text: String,
    },
    components: {
        YoutubeChannels,
    },
    data() {
        return {
            parsedHtmlText: '',
            youtubeChannels: [],
        };
    },
    mounted() {
        // adding stopPropagation to dynamic links so they work. todo: rewrite component with better approach
        const container = this.$refs.textContainer;
        container.addEventListener('click', this.handleLinkClick);
    },
    beforeDestroy() {
        const container = this.$refs.textContainer;
        container.removeEventListener('click', this.handleLinkClick);
    },
    methods: {
        handleLinkClick(event) {
            if ('A' === event.target.tagName && event.target.href.match(/hashtag/g)) {
                event.stopPropagation();
            }
        },
        convertToHTML(value) {
            this.youtubeChannels = [];

            value = value
                .replace(/&amp;/g, '&')
                .replace(/\n/g, '<br>')
                .replace(REGEX_URL, this.urlReplacer);
            value = this.proceedHashtags(value);

            return value;
        },
        proceedHashtags(text) {
            return addHtmlHashtagsToText(text, `${this.$routing.generate('homepage')}?hashtag=$1`);
        },
        isYoutube(url) {
            return url.match(REGEX_YOUTUBE);
        },
        isYoutubeChannel(url) {
            return url.match(REGEX_YOUTUBE_CHANNEL);
        },
        isImage(url) {
            return url.match(REGEX_IMAGE);
        },
        convertToLink(url) {
            const mainUrl = url;
            if (!url.match(/https?:\/\//g)) {
                url = `http://${url}`;
            }

            return `<a rel="noopener nofollow" target="_blank" href="${url}">${mainUrl}</a>`;
        },
        convertToYoutube(url) {
            const videoId = url.match(REGEX_YOUTUBE_URL_ID);
            const path = videoId ? videoId[1] : url;

            return `<iframe
                    class='position-relative'
                    width='100%'
                    height='315'
                    src='https://www.youtube.com/embed/${path}'
                    title='YouTube'
                    frameborder='0'
                    allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture'
                    allowfullscreen
                ></iframe>`;
        },
        convertToYoutubeChannel(url) {
            this.youtubeChannels.push(url);

            return url;
        },
        convertToImage(url) {
            if (!url.match(/https?:\/\//g)) {
                url = `http://${url}`;
            }
            if (url.match(/(www\.)/g) ) {
                url = url.replace('www.', '');
            }

            return `<br><img class="mw-100" src="${url}" />`;
        },
        urlReplacer(url) {
            if (this.isYoutubeChannel(url)) {
                return this.convertToYoutubeChannel(url);
            }

            if (this.isYoutube(url)) {
                return this.convertToYoutube(url);
            }

            if (this.isImage(url)) {
                const link = this.convertToLink(url);
                const image = this.convertToImage(url);

                return link + image;
            }

            return this.convertToLink(url);
        },
    },
    computed: {
        hasYoutubeChannels() {
            return 0 < this.youtubeChannels.length;
        },
        parsedText: function() {
            return this.convertToHTML(this.text);
        },
    },
};
</script>
