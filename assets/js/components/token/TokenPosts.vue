<template>
    <posts
        :token="token"
        :posts="localPosts"
        :posts-amount="localPostsAmount"
        :show-edit="isOwner"
        :logged-in="loggedIn"
        :is-owner="isOwner"
        :title="$t('page.pair.latest_news')"
        :subunit="tokenSubunit"
        :is-mobile-screen="isMobileScreen"
        :disable-infinite-scroll-on-mobile="true"
        :token-page="true"
        @go-to-trade="$emit('go-to-trade', $event)"
        @go-to-post="$emit('go-to-post', $event)"
    />
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import Posts from '../posts/Posts';

export default {
    name: 'TokenPosts',
    components: {
        Posts,
    },
    props: {
        token: Object,
        posts: Array,
        postsAmount: Number,
        loggedIn: Boolean,
        isOwner: Boolean,
        tokenSubunit: Number,
        isMobileScreen: Boolean,
    },
    data() {
        return {
            localPosts: [],
            localPostsAmount: 0,
        };
    },
    created() {
        // pass php-generated data only first time, otherwise use vuex data
        if (!this.isPostsInitialized) {
            this.localPosts = this.posts;
            this.localPostsAmount = this.postsAmount;
            this.setIsPostsInitialized(true);
        } else {
            this.localPosts = this.storagePosts;
            this.localPostsAmount = this.storagePostsAmount;
        }
    },
    computed: {
        ...mapGetters('pair', {
            isPostsInitialized: 'getIsPostsInitialized',
        }),
        ...mapGetters('posts', {
            storagePosts: 'getPosts',
            storagePostsAmount: 'getTokenPostsAmount',
        }),
    },
    methods: {
        ...mapMutations('pair', [
            'setIsPostsInitialized',
        ]),
    },
};
</script>
