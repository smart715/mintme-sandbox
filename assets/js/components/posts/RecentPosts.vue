<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column align-items-center">
        <template v-if="loading">
          <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </template>
        <template v-else-if="postsCount > 0">
            <post v-for="(n, i) in postsCount"
                  :post="posts[i]"
                  :key="i"
                  :index="i"
            />
        </template>
        <p v-else>
          {{ $t('post.no_recent_posts') }}
        </p>
    </div>
</template>

<script>
import Post from './Post';

export default {
    name: 'RecentPosts',
    components: {
        Post,
    },
    data() {
        return {
            posts: [],
            nextPage: 0,
            postsCount: 0,
            loading: true,
        };
    },
    methods: {
        fetchPosts() {
            this.$axios.single.get(this.$routing.generate('recent_posts', {nextPage: this.nextPage}))
            .then((res) => {
                if (res.data) {
                    this.postsCount += res.data.count;
                    this.posts = this.posts.concat(res.data.posts);
                    this.nextPage += 1;
                }

                this.loading = false;
            })
            .catch(() => {
                this.notifyError(this.$t('toasted.error.try_later'));
            });
        },
        onScrollDown(event) {
            let bottomOfWindow = document.documentElement.scrollTop +
                window.innerHeight === document.documentElement.offsetHeight;

            if (bottomOfWindow) {
                this.fetchPosts();
            }
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
