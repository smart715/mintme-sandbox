<template>
    <div class="card">
        <div class="card-header">
            <slot name="title">Posts</slot>
        </div>
        <div class="card-body posts">
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
            <div v-else>
                The token creator has not added any posts yet.
            </div>
            <a v-if="showReadMore"
                class="align-self-center"
                :href="readMoreUrl"
                @click.prevent="goToPosts"
            >
                All Posts
            </a>
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
        };
    },
    computed: {
        postsCount() {
            return Math.min(this.posts.length, this.max || Infinity);
        },
        showReadMore() {
            return !!(this.max && this.posts.length > this.max);
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
