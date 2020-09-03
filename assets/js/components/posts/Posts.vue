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
            readMoreFlag: false,
        };
    },
    mounted() {
        this.$nextTick(() => {
            if (typeof this.$refs.postsContainer !== 'undefined') {
                let postsContainer = this.$refs.postsContainer;

                console.error('Case before: ', postsContainer.clientHeight, postsContainer.scrollHeight, postsContainer.offsetHeight);

                if (postsContainer.clientHeight > 335) {
                    postsContainer.style.height = postsContainer.clientHeight - 20 + 'px';
                    this.readMoreFlag = true;

                    console.error('Case after: ', postsContainer.clientHeight, postsContainer.scrollHeight, postsContainer.offsetHeight);
                }
            }
        });
    },
    computed: {
        postsCount() {
            return Math.min(this.posts.length, this.max || Infinity);
        },
        showReadMore() {
            return !!(this.max && this.posts.length > this.max) || this.readMoreFlag;
        },
        classObject: function() {
            if (this.readMoreFlag) {
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
