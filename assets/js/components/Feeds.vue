<template>
    <div class="container">
        <div class="d-flex justify-content-center">
            <div class="col-12 text-center">
                <h1 class="mt-5 font-weight-semibold">
                    {{ $t('page.index.promo.trade.title') }}
                    <div class="d-inline-flex flex-wrap">
                        <span class="font-style-italic" v-html="$t('page.index.promo.trade.mintme')"/>
                        <span
                            class="color-title-question"
                            v-html="$t('page.index.promo.trade.mintme_question')"
                        />
                    </div>
                </h1>
                <p class="mt-3 subtitle-text-color" v-html="$t('page.index.promo.trade.subtitle')"/>
            </div>
        </div>
        <div class="row mt-4 mb-2">
            <div class="col">
                <div class="row">
                    <div class="col-12 text-center">
                        <h1 class="mt-5 font-weight-semibold" v-html="$t('page.pair.recent_activities')" />
                    </div>
                    <div class="col-12 mt-3">
                        <feed
                            :is-home-page="true"
                            :show-more-home-page="showMore"
                            :items-prop="itemsProp"
                            :mercure-hub-url="mercureHubUrl"
                            :min="6"
                            :max="30"
                        ></feed>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="row">
                    <div class="col-12 text-center">
                        <h1 class="mt-5 font-weight-semibold" v-html="$t('page.pair.recent_feed')"/>
                    </div>
                    <div class="col-12 mt-3">
                        <recent-feed
                            :is-home-page="true"
                            :show-more="showMore"
                            :logged-in="loggedIn"
                            :view-only="viewOnly"
                            :own-deployed-tokens="ownDeployedTokens"
                            :is-authorized-for-reward="isAuthorizedForReward"
                            :min="3"
                            :max="15"
                        ></recent-feed>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mb-4">
            <button class="btn btn-lg button-secondary rounded-pill" @click="toggle">
                <span class="pt-2 pb-2 pl-3 pr-3">
                    {{ showMoreText }}
                </span>
            </button>
        </div>
    </div>
</template>

<script>
import Feed from './Feed.vue';
import RecentFeed from './posts/RecentFeed.vue';

export default {
    name: 'Feeds',
    components: {
        Feed,
        RecentFeed,
    },
    props: {
        itemsProp: Array,
        mercureHubUrl: String,
        isAuthorizedForReward: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
        viewOnly: Boolean,
        ownDeployedTokens: {
            type: Array,
            default: () => [],
        },
        min: Number,
        max: Number,
    },
    data() {
        return {
            showMore: false,
        };
    },
    computed: {
        showMoreText() {
            return this.showMore ? this.$t('page.index.see_less') : this.$t('page.index.see_more');
        },
    },
    methods: {
        toggle() {
            this.showMore = !this.showMore;
        },
    },
};
</script>
