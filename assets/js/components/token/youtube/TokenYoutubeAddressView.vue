<template>
    <div class="row">
        <div class="col text-truncate">
            {{ $t('token.youtube.view_label') }}
            <a
                :href="youTubeUrl"
                target="_blank"
                rel="nofollow"
                v-b-tooltip.hover :title="youTubeUrl"
            >
                {{ youTubeUrl }}
            </a>
        </div>
        <div class="col-auto">
            <div ref="ytButtonContainer" class="d-block-inline"></div>
        </div>
        <div class="col-auto social-help">
            <guide>
                <template slot="header">
                    {{ $t('token.youtube.guide_header') }}
                </template>
                <template slot="body">
                    <span v-html="this.$t('token.youtube.guide_body')"></span>
                </template>
            </guide>
        </div>
    </div>
</template>

<script>
import gapi from 'gapi';
import {VBTooltip} from 'bootstrap-vue';
import {FiltersMixin} from '../../../mixins';
import Guide from '../../Guide';

export default {
    name: 'TokenYoutubeAddressView',
    components: {
        Guide,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
    ],
    props: {
        channelId: String,
        clientId: String,
    },
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
