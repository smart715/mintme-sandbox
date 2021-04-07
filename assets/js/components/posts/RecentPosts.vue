<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column align-items-center">
        <template v-if="loading">
          <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </template>
        <template v-else-if="postsCount > 0">
            <div v-for="(n, i) in postsCount" :id="i" :key="i" class="post">
                <h3 class="post-title">
                    <a :href="posts[i].postLink" class="text-decoration-none text-white">
                      {{ posts[i].title }}
                    </a>
                    <b>by</b>
                    <small>
                        <a :href="posts[i].tokenLink" class="text-decoration-none text-white">
                            <img
                                :src="posts[i].tokenImageUrl.avatar_small"
                                class="rounded-circle d-inline-block"
                                alt="avatar"
                            >
                            {{ posts[i].token }}
                        </a>
                    </small>
                </h3>
                <div>
                    <a :href="posts[i].authorLink" class="text-decoration-none text-white">
                        <img
                            :src="posts[i].authorImage.avatar_small"
                            class="rounded-circle d-inline-block"
                            alt="avatar"
                        >
                        {{ posts[i].author }}
                    </a>
                    <a :href="posts[i].postLink" class="text-decoration-none post-date">
                        {{ moment(posts[i].createdAt.date).format('H:mm, MMM D, YYYY') }}
                    </a>
                    <copy-link :content-to-copy="posts[i].HashTagLink" class="c-pointer ml-1">
                        <font-awesome-icon :icon="['far', 'copy']"/>
                    </copy-link>
                </div>
                <template>
                    <p v-if="posts[i].content" class="post-content my-2">
                      <bbcode-view :value="posts[i].content"/>
                    </p>
                    <p v-else>
                      {{ $t('post.logged_in.1') }} <a href="#" @click.prevent="$emit('go-to-trade', post.amount)">{{posts[i].amount | toMoney | formatMoney}} {{ posts[i].token }}</a> {{ $t('post.logged_in.2') }}
                    </p>
                </template>
                <a href="#" class="hover-icon text-decoration-none text-white">
                    <font-awesome-icon
                        class="c-pointer align-middle"
                        icon="comment"
                        transform="grow-1.5"
                    />
                    <span class="social-link ml-1">{{ posts[i].commentCount }} {{ $t('post.comments') }}</span>
                </a>
            </div>
        </template>
        <p v-else>
          {{ $t('post.no_recent_posts') }}
        </p>
    </div>
</template>

<script>
import BbcodeView from '../bbcode/BbcodeView';
import CopyLink from '../CopyLink';
import {NotificationMixin} from '../../mixins';

export default {
    name: 'RecentPosts',
    mixins: [
        NotificationMixin,
    ],
    components: {
        BbcodeView,
        CopyLink,
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
