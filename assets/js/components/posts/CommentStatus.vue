<template>
    <div class="d-flex align-items-center">
        <post-likes :is-liked="isLiked" :likes="likes" @like="likeComment" />
        <comment-status-tip
            v-if="isLoggedIn && !isAuthor"
            :is-logged-in="isLoggedIn"
            :user-has-deployed-token="userHasDeployedTokens"
            :is-tipped="comment.tipped"
            @tip="$emit('tip')"
        />
    </div>
</template>

<script>
import PostLikes from './PostLikes';
import CommentStatusTip from './CommentStatusTip';
import {mapGetters} from 'vuex';
import {NotificationMixin} from '../../mixins';

export default {
    name: 'CommentStatus',
    components: {
        PostLikes,
        CommentStatusTip,
    },
    mixins: [NotificationMixin],
    props: {
        isLoggedIn: Boolean,
        comment: Object,
        userHasDeployedTokens: Boolean,
    },
    data() {
        return {
            isRequesting: false,
            likes: 0,
            isLiked: false,
        };
    },
    watch: {
        comment() {
            this.likes = this.comment.likeCount;
            this.isLiked = this.comment.liked;
        },
    },
    created() {
        this.likes = this.comment.likeCount;
        this.isLiked = this.comment.liked;
    },
    computed: {
        ...mapGetters('user', {
            userId: 'getId',
        }),
        isAuthor() {
            return this.comment?.author?.id === this.userId;
        },
    },
    methods: {
        async likeComment() {
            if (!this.isLoggedIn) {
                location.href = this.$routing.generate('login', {}, true);

                return;
            }

            if (this.isRequesting) {
                return;
            }

            this.saveLike();

            this.isRequesting = true;

            try {
                await this.$axios.single.post(this.$routing.generate('like_comment', {commentId: this.comment.id}));
            } catch (e) {
                this.notifyError(e.response.data?.message ?? 'Error liking the comment');
                this.saveLike();
            } finally {
                this.isRequesting = false;
            }
        },
        saveLike() {
            if (this.isLiked) {
                this.likes--;
                this.isLiked = false;

                return;
            }

            this.likes++;
            this.isLiked = true;
        },
    },
};
</script>
