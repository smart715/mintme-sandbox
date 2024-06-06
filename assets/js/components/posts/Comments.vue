<template>
    <div class="comments">
        <comment-form
            class="card py-4 px-3"
            :logged-in="loggedIn"
            :api-url="apiUrl"
            :comment-min-amount="commentMinAmount"
            :is-owner="isOwner"
            :post="post"
            @submitted="$emit('add-comment', $event)"
            @error="onCreateCommentError"
            @cancel="$emit('cancel')"
            reset-after-submit
        />
        <div class="my-3">
            <template v-if="commentsCount > 0">
                <comment
                    v-for="(comment, i) in comments"
                    :comment-prop="comment"
                    :index="i"
                    :key="comment.id"
                    :logged-in="loggedIn"
                    :user-has-deployed-tokens="ownDeployedTokens && ownDeployedTokens.length !== 0"
                    :is-owner="isOwner"
                    :comment-min-amount="commentMinAmount"
                    :top-holders="topHolders"
                    @tip="onCommentTip(comment)"
                    @update-comment="updateComment($event, i)"
                    @delete-comment="onDeleteComment"
                />
            </template>
            <div v-else class="text-center w-100">
              {{ $t('post.no_one_commented') }}
            </div>
        </div>
        <confirm-modal
            :visible="isDeleteConfirmVisible"
            type="delete"
            :show-image="false"
            :close-on-confirm="false"
            :submitting="isDeleting"
            @confirm="deleteCommentConfirm"
            @close="closeDeleteConfirmModal"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('comment.confirm_delete') }}
            </p>
        </confirm-modal>
        <tip-comment-modal
            v-if="ownDeployedTokens && ownDeployedTokens.length"
            :visible="tipModalVisible"
            :deployed-tokens="ownDeployedTokens"
            :comment="activeTipComment"
            @close="tipModalVisible = false"
        />
    </div>
</template>

<script>
import Comment from './Comment';
import CommentForm from './CommentForm';
import {NotificationMixin} from '../../mixins';
import ConfirmModal from '../modal/ConfirmModal';
import TipCommentModal from '../modal/TipCommentModal';
import {HTTP_ACCESS_DENIED} from '../../utils/constants';

export default {
    name: 'Comments',
    mixins: [
        NotificationMixin,
    ],
    components: {
        Comment,
        CommentForm,
        ConfirmModal,
        TipCommentModal,
    },
    props: {
        comments: {
            type: Array,
            default: () => [],
        },
        loggedIn: Boolean,
        post: Object,
        ownDeployedTokens: Array,
        commentMinAmount: Number,
        isOwner: Boolean,
        topHolders: Array,
    },
    data() {
        return {
            isDeleteConfirmVisible: false,
            commentToDelete: null,
            isDeleting: false,
            tipModalVisible: false,
            activeTipComment: null,
        };
    },
    computed: {
        commentsCount() {
            return this.comments.length;
        },
        apiUrl() {
            return this.$routing.generate('add_comment', {id: this.post.id});
        },
    },
    methods: {
        updateComment(comment, i) {
            this.$emit('update-comment', comment);
        },
        onCreateCommentError() {
            this.notifyError(this.$t('comment.create_failed'));
        },
        onDeleteComment(comment) {
            this.isDeleteConfirmVisible = true;
            this.commentToDelete = comment;
        },
        deleteCommentConfirm() {
            if (this.isDeleting) {
                return;
            }

            this.isDeleting = true;
            this.$axios.single.post(this.$routing.generate('delete_comment', {commentId: this.commentToDelete.id}))
                .then((res) => {
                    this.$emit('delete-comment', this.commentToDelete);
                    this.closeDeleteConfirmModal();
                    this.notifySuccess(res.data.message);
                })
                .catch((error) => {
                    if (HTTP_ACCESS_DENIED === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('comment.delete_failed');
                    }
                })
                .finally(() => {
                    this.isDeleting = false;
                });
        },
        closeDeleteConfirmModal() {
            this.isDeleteConfirmVisible = false;
            this.commentToDelete = null;
        },
        onCommentTip(comment) {
            this.activeTipComment = comment;
            this.tipModalVisible = true;
        },
    },
};
</script>
