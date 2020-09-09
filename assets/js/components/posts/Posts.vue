<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">Posts</slot>
        </div>
        <div
            class="card-body posts overflow-y-hidden position-relative"
            ref="postsContainer"
        >
            <template v-if="posts.length > 0">
                <post v-for="(n, i) in postsCount"
                    :post="posts[i]"
                    :key="i"
                    :index="i"
                    @delete-post="$emit('delete-post', $event)"
                    :show-edit="showEdit"
                    @go-to-trade="$emit('go-to-trade', $event)"
                    :logged-in="loggedIn"
                />
            </template>
            <div v-else :class="{ 'position-absolute top-50': tokenPage }">
                The token creator has not added any posts yet.
            </div>
            <div v-if="showReadMore" :class="classObject">
                <a
                    class="align-self-center"
                    :href="readMoreUrl"
                    @click.prevent="goToPosts"
                >
                    More posts
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
        };
    },
    mounted() {
        if (
            typeof this.$refs.postsContainer !== 'undefined'
            && this.posts.length > 0 && this.loggedIn && this.max > 0
        ) {
            let postsContainer = this.$refs.postsContainer;
            let posts = postsContainer.getElementsByClassName('post');
            let postsHeight = 0;

            for (let i = 0; i < posts.length; i++) {
                postsHeight += posts[i].offsetHeight + 10; // `10px` - margin
            }

            if (
                postsContainer.scrollHeight > postsContainer.clientHeight
                || postsHeight > 317
            ) {
                this.readMore = true;
            }
        }
    },
    computed: {
        postsCount() {
            return Math.min(this.posts.length, this.max || Infinity);
        },
        showReadMore() {
            return !!(this.max && this.posts.length > this.max) || this.readMore;
        },
        classObject: function() {
            if (this.tokenPage && this.readMore && this.max > 0) {
                return {
                    'show-more-container': true,
                };
            }
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
    },
};
</script>
