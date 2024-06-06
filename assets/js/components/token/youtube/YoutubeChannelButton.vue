<template>
    <div class="card youtube-channel my-2">
        <a
            rel="noopener nofollow"
            class="channel-link d-flex p-2"
            target="_blank"
            :href="channelInfo.url"
            @click.stop=""
        >
            <div class="channel-thumbnail d-flex align-items-center flex-shrink-0 justify-content-center">
                <img :src="channelImg" class="rounded-circle img-fluid"  alt="channel-avatar"/>
            </div>
            <div class="word-break flex-grow-1 pl-3">
                <p>youtube.com {{ channelInfo.section }}</p>
                <template v-if="channelInfo.loaded">
                    <p><b>{{ channelInfo.name }}</b></p>
                    <p>{{ description }}</p>
                </template>
                <template v-else>
                    <div class="text-center pt-4">
                        <font-awesome-icon icon="circle-notch" class="loading-spinner" fixed-width spin />
                    </div>
                </template>
            </div>
        </a>
    </div>
</template>

<script>
import {NotificationMixin} from '../../../mixins';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'YoutubeChannelButton',
    mixins: [NotificationMixin],
    components: {
        FontAwesomeIcon,
    },
    props: {
        channelInfo: Object,
    },
    computed: {
        channelImg() {
            return this.channelInfo.loaded
                ? this.channelInfo.img
                : require('../../../../img/ms-icon-144x144.png');
        },
        description() {
            if (!this.channelInfo.loaded) {
                return false;
            }

            return 80 < this.channelInfo.description.length
                ? this.channelInfo.description.substring(0, 77) + '...'
                : this.channelInfo.description;
        },
    },
};
</script>
