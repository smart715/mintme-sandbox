<template>
    <div class="d-flex-inline">
        <div class="display-text">
            Youtube:
            <a
                :href="youTubeUrl"
                target="_blank"
                rel="nofollow"
            >
                {{ youTubeUrl }}
            </a>
            <div ref="ytButtonContainer" class="d-block-inline"></div>
            <guide>
                <template slot="header">
                    Youtube
                </template>
                <template slot="body">
                    Link to token creator’s YouTube. Before adding it, we confirmed ownership.
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import {FiltersMixin} from '../../../mixins';
import gapi from 'gapi';
import Guide from '../../Guide';

export default {
    name: 'TokenYoutubeAddressView',
    props: {
        channelId: String,
        clientId: String,
    },
    components: {
        Guide,
    },
    mixins: [FiltersMixin],
    computed: {
        youTubeUrl: function() {
            return this.buildYoutubeUrl(this.channelId);
        },
    },
    watch: {
        channelId: function() {
            this.renderYtSubscribeButton(this.channelId);
        },
    },
    mounted() {
        if (this.channelId) {
            this.renderYtSubscribeButton(this.channelId);
        }
    },
    methods: {
        buildYoutubeUrl: function(id) {
            return 'https://www.youtube.com/channel/' + id;
        },
        renderYtSubscribeButton: function(channelId) {
            let options = {'channelid': channelId};
            gapi.ytsubscribe.render(this.$refs.ytButtonContainer, options);
        },
    },
};
</script>

<style lang="sass" scoped>
    .display-text
        display: inline-block
        width: 100%
        text-overflow: ellipsis
</style>
