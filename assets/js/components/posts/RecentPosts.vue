<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column align-items-center">
        <font-awesome-icon v-if="loading" icon="circle-notch" spin class="loading-spinner" fixed-width />
        <template v-else-if="hasPosts">
            <post v-for="(n, i) in posts.length"
                  :post="posts[i]"
                  :key="i"
                  :index="i"
                  :recent-post="true"
            />
        </template>
        <p v-else>
            {{ $t('post.no_recent_posts') }}
        </p>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

import Post from './Post';

library.add(faCircleNotch);

export default {
    name: 'RecentPosts',
    components: {
        Post,
        FontAwesomeIcon,
    },
    props: {
        postsProp: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            posts: this.postsProp,
            nextPage: 0,
            loading: true,
        };
    },
    methods: {
        fetchPosts() {
            this.$axios.single.get(this.$routing.generate('recent_posts', {nextPage: this.nextPage}))
            .then((res) => {
                if (res.data) {
                    this.posts = this.posts.concat(res.data.posts);
                    this.nextPage += 1;
                }

                this.loading = false;
            })
            .catch(() => {
                this.notifyError(this.$t('toasted.error.try_later'));
            });
        },
        onScrollDown() {
            let bottomOfWindow = document.documentElement.scrollTop +
                window.innerHeight === document.documentElement.offsetHeight;

            if (bottomOfWindow) {
                this.fetchPosts();
            }
        },
    },
    computed: {
        hasPosts() {
            return this.posts.length > 0;
        },
    },
    mounted() {
        this.$nextTick(() => {
            window.addEventListener('scroll', this.onScrollDown);
            this.onScrollDown();
        });
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.onScrollDown);
    },
};
</script>
