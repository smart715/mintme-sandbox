<template>
    <div id="posts-container" ref="postsContainer" class="w-100 d-flex flex-column align-items-center">

        <div v-for="(n, i) in postsCount" :id="i" class="post">
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
            <a href="#" class="hover-icon text-decoration-none text-white">
                <font-awesome-icon
                    class="c-pointer align-middle"
                    icon="comment"
                    transform="grow-1.5"
                />
                <span class="social-link ml-1">{{ posts[i].commentCount }} {{ $t('post.comments') }}</span>
            </a>
            <a href="#" @click="sharePost">
                After
            </a>
        </div>
    </div>
</template>

<script>
import CopyLink from "../CopyLink";
import moment from 'moment';

export default {
    name: 'RecentPosts',
    components: {
        CopyLink,
    },
    data() {
        return {
            posts: [],
            nextPage: 1,
            postsCount: 0,
        };
    },
    methods: {
        fetchPosts() {
            this.$axios.single.get(this.$routing.generate('recent_posts', {nextPage: this.nextPage}))
            .then((res) => {
                this.postsCount += res.data.count;
                this.posts = res.data.posts;
                this.nextPage = res.data.nextPage;
            })
            .catch((error) => {
                console.log(error);
            });
        },
        onScroll (event) {
            let bottomOfWindow = document.documentElement.scrollTop +
                window.innerHeight === document.documentElement.offsetHeight;

            if (bottomOfWindow) {
                if (this.postCount < 40) {
                  this.postCount += 10;
                }
            }

            console.log('scrolling');
        },
        sharePost() {
            console.log('share post');
        },
    },
    mounted () {
        this.$nextTick(function() {
            window.addEventListener('scroll', this.onScroll);
            this.onScroll(); // needed for initial loading on page
        });
    },
    created () {
        this.fetchPosts();
    },
    beforeDestroy() {
      window.removeEventListener('scroll', this.onScroll);
    }
};
</script>
