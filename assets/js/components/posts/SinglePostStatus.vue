<template>
    <div class="d-flex align-items-center">
        <div
            class="d-flex align-items-center mr-3 font-size-2 text-subtitle"
            :class="{'c-pointer': !isSinglePost}"
            @click="goToSinglePost"
        >
            <font-awesome-icon
                :icon="['far', 'comment']"
                class="mr-2"
                transform="up-1.5"
            />
            {{ post.commentsCount }}
        </div>
        <post-likes
            :is-liked="post.isUserAlreadyLiked"
            :likes="post.likes"
            @like="likePost"
        />
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faComment} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import PostLikes from './PostLikes';
import {mapGetters, mapMutations} from 'vuex';
import {NotificationMixin, StringMixin} from '../../mixins';

library.add(faComment);

export default {
    name: 'SinglePostStatus',
    mixins: [StringMixin, NotificationMixin],
    components: {
        FontAwesomeIcon,
        PostLikes,
    },
    props: {
        isLoggedIn: Boolean,
        isSinglePost: Boolean,
    },
    data() {
        return {
            requesting: false,
        };
    },
    computed: {
        ...mapGetters('posts', {
            post: 'getSinglePost',
        }),
        singlePageUrl() {
            return this.post.slug
                ? this.$routing.generate(
                    'token_show_post',
                    {
                        name: this.dashedString(this.post.token.name),
                        slug: this.post.slug,
                    },
                    true)
                : this.$routing.generate('show_post', {id: this.post.id}, true);
        },
    },
    methods: {
        ...mapMutations('posts', [
            'likeSinglePost',
            'removeSinglePostLike',
        ]),
        likePost() {
            if (this.requesting) {
                return;
            }

            if (!this.isLoggedIn) {
                location.href = this.$routing.generate('login', {}, true);
                return;
            }

            this.saveLike();

            this.requesting = true;
            this.$axios.single.post(this.$routing.generate('like_post', {id: this.post.id}))
                .catch((err) => {
                    this.notifyError(err.response.data?.message ?? 'Error liking the post');
                    this.saveLike();
                })
                .finally(() => {
                    this.requesting = false;
                });
        },
        saveLike() {
            this.post.isUserAlreadyLiked
                ? this.removeSinglePostLike()
                : this.likeSinglePost();
        },
        goToSinglePost() {
            if (this.isSinglePost) {
                return;
            }

            window.location.href = this.isLoggedIn ?
                this.singlePageUrl:
                this.$routing.generate('login', {}, true);
        },
    },
};
</script>
