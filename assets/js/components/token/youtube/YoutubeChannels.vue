<template>
    <div class="pt-1">
        <youtube-channel-button
            v-for="(channelInfo, key) in youtubeChannelsInfo"
            :key="key"
            :channel-info="channelInfo"
        />
    </div>
</template>

<script>
import {NotificationMixin} from '../../../mixins';
import YoutubeChannelButton from './YoutubeChannelButton.vue';
import {REGEX_YOUTUBE_CHANNEL_ID} from '../../../utils/regex';

export default {
    name: 'YoutubeChannels',
    components: {YoutubeChannelButton},
    props: {
        urls: Array,
    },
    mixins: [NotificationMixin],
    data() {
        return {
            youtubeChannelsInfo: [],
            fetchedInfo: [],
            channelsIds: [],
        };
    },
    mounted() {
        this.updateChannelsInfo();
    },
    methods: {
        async updateChannelsInfo() {
            this.processChannelUrls();
            await this.fetchChannelsInfo();
            this.processFetchedInfo();
        },
        processChannelUrls() {
            this.youtubeChannelsInfo = [];
            this.channelsIds = [];

            this.urls.forEach((url) => {
                const regex = url.match(REGEX_YOUTUBE_CHANNEL_ID);

                if (!regex || 2 > regex.length) {
                    return;
                }

                this.channelsIds.push(regex[1]);
                this.youtubeChannelsInfo.push({
                    url: url,
                    id: regex[1],
                    section: regex[2]
                        ? '- ' + regex[2]
                        : null,
                    loaded: false,
                });
            });
        },
        async fetchChannelsInfo() {
            try {
                const response = await this.$axios.single.get(this.$routing.generate('youtube_channels_info', {
                    channelsIdsJson: JSON.stringify(this.channelsIds),
                }));

                this.fetchedInfo = response.data ?? [];
            } catch (error) {
                this.notifyError(this.$t('youtube.channel_info.error'));
                this.$logger.error('Can not load youtube channel from url', error);
            }
        },
        processFetchedInfo() {
            this.youtubeChannelsInfo = this.youtubeChannelsInfo.map((element) => {
                if (!this.fetchedInfo[element.id]) {
                    return element;
                }

                element = {...element, ...this.fetchedInfo[element.id]};
                element.loaded = true;

                return element;
            });
        },
    },
    watch: {
        urls() {
            this.updateChannelsInfo();
        },
    },
};
</script>

