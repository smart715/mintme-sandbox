<template>
    <div>
        <single-post
            :token="singlePost.token"
            :show-edit="isOwner"
            :logged-in="isLoggedIn"
            :is-owner="isOwner"
            :subunit="tokenSubunit"
            @go-to-trade="$emit('go-to-trade')"
            @post-deleted="$emit('post-deleted')"
        ></single-post>
        <single-post-comments
            v-if="singlePost.content"
            :logged-in="isLoggedIn"
            :comment-min-amount="commentMinAmount"
            :is-owner="isOwner"
            :is-loading="isCommentsLoading"
            :top-holders="topHolders"
            class="mt-3"
        ></single-post-comments>
        <div v-else class="card text-center p-3">
            {{ $t('comment.min_amount', {amount: postTokenAmount, token: singlePost.token.name}) }}
        </div>
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import {NotificationMixin} from '../../mixins';
import {formatMoney, toMoney} from '../../utils';
import SinglePost from '../posts/SinglePost';
import SinglePostComments from '../posts/SinglePostComments';

export default {
    name: 'TokenSinglePostPage',
    components: {
        SinglePost,
        SinglePostComments,
    },
    mixins: [NotificationMixin],
    props: {
        isOwner: Boolean,
        isLoggedIn: Boolean,
        commentMinAmount: Number,
        tokenSubunit: Number,
        initialPost: Object,
        initialComments: {
            type: Array,
            default: () => [],
        },
        topHolders: Array,
    },
    data() {
        return {
            isCommentsLoading: false,
        };
    },
    computed: {
        ...mapGetters('posts', {
            singlePost: 'getSinglePost',
            posts: 'getPosts',
            comments: 'getComments',
        }),
        postTokenAmount() {
            if (!this.singlePost || !this.singlePost.amount) {
                return 0;
            }

            return formatMoney(toMoney(this.singlePost.amount));
        },
    },
    methods: {
        ...mapMutations('posts', [
            'setSinglePost',
            'setComments',
        ]),
        async loadComments(post) {
            this.isCommentsLoading = true;

            try {
                const response = await this.$axios.retry.get(this.$routing.generate(
                    'get_post_comments',
                    {id: post.id},
                ));

                this.setComments(response.data);
            } catch (err) {
                this.notifyError(this.$t('toasted.error.try_reload'));
                this.$logger.error('Can not unblock profile', err);
            } finally {
                this.isCommentsLoading = false;
            }
        },
    },
    beforeMount() {
        if (this.singlePost) {
            this.loadComments(this.singlePost);

            return;
        }

        let postToSet = this.initialPost;

        if (this.posts && this.initialPost) {
            const storagePostInstance = this.posts.find((p) => p.id === this.initialPost.id);

            if (storagePostInstance) {
                postToSet = storagePostInstance;
            }
        }

        this.setSinglePost(postToSet);
        this.setComments(this.initialComments);
    },
};
</script>
