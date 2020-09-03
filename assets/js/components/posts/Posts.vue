<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">Posts</slot>
        </div>
        <div
            class="card-body posts"
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
            <div v-else class="position-absolute top-50">
                The token creator has not added any posts yet.
            </div>
            <a v-if="showReadMore"
                class="align-self-center"
                :href="readMoreUrl"
                @click.prevent="goToPosts"
            >
                More posts
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
    mounted() {
        document.addEventListener('DOMContentLoaded', () => {
            let container1 = document.querySelectorAll('.posts')[0];
            console.error('Case 1: ', container1.clientHeight, container1.scrollHeight, container1.offsetHeight);

            let posts1 = this.$refs.postsContainer;
            console.error('Case 2: ', posts1.clientHeight, posts1.scrollHeight, posts1.offsetHeight);
        });

        this.$nextTick(() => {
            let container2 = document.querySelectorAll('.posts')[0];
            console.error('Case 3: ', container2.clientHeight, container2.scrollHeight, container2.offsetHeight);

            let posts2 = this.$refs.postsContainer;
            console.error('Case 4: ', posts2.clientHeight, posts2.scrollHeight, posts2.offsetHeight);
        });
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
