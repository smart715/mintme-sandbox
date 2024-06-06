<template>
    <div class="mx-1 my-2">
        <button
            v-if="follower"
            class="btn btn-primary"
            @click="unfollowToken"
        >
            {{ $t('page.pair.follow_btn.unfollow') }}
        </button>
        <button
            v-else
            class="btn btn-primary"
            v-b-tooltip="tooltipConfig"
            @click="followToken"
        >
            {{ $t('page.pair.follow_btn.follow') }}
        </button>
        <guide class="tooltip-center font-size-tooltip">
            <template slot="body">
                {{ $t('page.pair.follow_btn.tooltip') }}
            </template>
        </guide>
    </div>
</template>

<script>

import {mapGetters} from 'vuex';
import Guide from '../Guide';
import {NotificationMixin} from '../../mixins';
import {TOKEN_FOLLOW_STATUS} from '../../utils/constants';
import {VBTooltip} from 'bootstrap-vue';

export default {
    name: 'TokenFollowButton',
    components: {
        Guide,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        followerProp: Boolean,
        tokenName: String,
        mercureHubUrl: String,
        isOwner: Boolean,
    },
    data() {
        return {
            follower: this.followerProp,
            loginUrl: this.$routing.generate('login'),
            topic: 'update-follow-status',
        };
    },
    mounted() {
        const es = new EventSource(this.mercureHubUrl + '?topic=' + encodeURIComponent(this.topic));

        es.onmessage = (e) => {
            const data = JSON.parse(e.data);

            if (this.getUserId === data.user.id) {
                this.follower = TOKEN_FOLLOW_STATUS.FOLLOWED === data.followStatus;
            }
        };
    },
    computed: {
        ...mapGetters('user', {
            getUserId: 'getId',
        }),
        userLoggedIn: function() {
            return null !== this.getUserId;
        },
        tooltipConfig: function() {
            return {
                disabled: this.userLoggedIn,
                customClass: 'follow-log-in-modal',
                title: this.$t('page.pair.follow_btn.login_modal', {loginUrl: this.loginUrl}),
                placement: 'bottom',
                trigger: 'click blur',
                html: true,
            };
        },
    },
    methods: {
        followToken: async function() {
            if (!this.userLoggedIn) {
                return;
            }

            if (this.isOwner) {
                this.notifyError(this.$t('page.pair.follow_btn.message.owner_cant_follow'));
                return;
            }

            try {
                const response = await this.$axios.single.patch(
                    this.$routing.generate('token_follow', {tokenName: this.tokenName})
                );

                this.follower = true;
                this.notifySuccess(response.data.message);
            } catch (error) {
                this.$logger.error('Can\'t follow token', error);

                this.notifyError(error?.response?.data?.message || this.$t('api.something_went_wrong'));
            }
        },
        unfollowToken: async function() {
            try {
                const response = await this.$axios.single.patch(
                    this.$routing.generate('token_unfollow', {tokenName: this.tokenName})
                );

                this.follower = false;
                this.notifySuccess(response.data.message);
            } catch (error) {
                this.$logger.error('Can\'t unfollow token', error);

                this.notifyError(error?.response?.data?.message || this.$t('api.something_went_wrong'));
            }
        },
    },
};
</script>
