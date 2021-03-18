<template>
    <div class="card h-100 posts-container">
        <div class="card-header">
            <slot name="title">{{ $t('page.pair.posts_title') }}</slot>
        </div>
        <div
            class="card-body posts overflow-hidden position-relative"
        >
            <div 
                id="posts-container"
                ref="postsContainer"
                class="w-100 d-flex flex-column align-items-center"
                :class="!hasPosts ? 'h-100 justify-content-center' : ''"
            >
                <template v-if="hasPosts">
                    <post v-for="(n, i) in postsCount"
                          :post="posts[i]"
                          :key="i"
                          :index="i"
                          @delete-post="$emit('delete-post', $event)"
                          :show-edit="showEdit"
                          @go-to-trade="$emit('go-to-trade', $event)"
                          :logged-in="loggedIn"
                          @go-to-post="$emit('go-to-post', $event)"
                    />
                </template>
                <div v-else class="absolute-center">
                    {{ $t('post.not_any_post') }}
                </div>
            </div>
            <div v-if="showReadMore" class="read-more">
                <a
                    class="align-self-center all-posts-link"
                    :href="readMoreUrl"
                    @click.prevent="goToPosts"
                >
                    {{ $t('posts.all') }}
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import Post from './Post';

export default {
    name: 'Posts',
    components: {Post},
    props: {
        posts: {
            type: Array,
            default: () => [],
        },
        max: {
            type: Number,
            default: null,
        },
        tokenName: String,
        tokenPage: {
            type: Boolean,
            default: false,
        },
        showEdit: {
            type: Boolean,
            default: false,
        },
        loggedIn: Boolean,
    },
    data() {
        return {
            readMoreUrl: this.$routing.generate('token_show', {name: this.tokenName, tab: 'posts'}),
            readMore: false,
            resizeObserver: null,
        };
    },
    mounted() {
        this.resizeObserver = new ResizeObserver(this.updateReadMore.bind(this));
        this.resizeObserver.observe(this.$refs.postsContainer);
    },
    computed: {
        postsCount() {
            return Math.min(this.posts.length, this.max || Infinity);
        },
        hasPosts() {
            return this.posts.length > 0;
        },
        showReadMore() {
            return !!(this.max && this.posts.length > this.max) || this.readMore;
        },
    },
    methods: {
        goToPosts() {
            if (this.tokenPage) {
                this.$emit('go-to-posts');
            } else {
                location.href = this.readMoreUrl;
            }
        },
        updateReadMore() {
            let posts = document.querySelector('.posts');
            let postsContainer = document.querySelector('#posts-container');
            this.readMore = postsContainer.clientHeight > posts.clientHeight;
        },
    },
    beforeDestroy() {
        this.resizeObserver.disconnect();
    },
};
</script>
