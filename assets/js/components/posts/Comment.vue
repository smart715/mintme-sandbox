<template>
    <div class="comment">
        <div>
            <a
                :href="profileUrl"
                class="text-white"
            >
                <img
                    :src="comment.author.profile.image.avatar_small"
                    class="rounded-circle d-inline-block"
                    alt="avatar"
                >
                {{ comment.author.profile.nickname }}
            </a>
            <span class="comment-date">
                {{ date }}
            </span>
            <button
                v-if="comment.deletable"
                class="btn btn-link p-0 delete-icon float-right text-decoration-none text-reset"
                :disabled="deleteDisabled"
                @click="showConfirm"
            >
                <font-awesome-icon
                    class="icon-default c-pointer align-middle"
                    icon="trash"
                    transform="shrink-4 up-1.5"
                />
            </button>
            <button
                v-if="comment.editable"
                class="btn btn-link p-0 comment-edit-icon float-right text-decoration-none text-reset"
                @click="editing = true"
            >
                <font-awesome-icon
                    class="icon-default c-pointer align-middle"
                    icon="edit"
                    transform="shrink-4 up-1.5"
                />
            </button>
        </div>
        <confirm-modal
            :visible="isConfirmVisible"
            @confirm="deleteComment"
            @close="closeConfirm"
            model-confirm-prop="confirm_modal.accept_delete"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('comment.confirm_delete') }}
            </p>
        </confirm-modal>
        <p
            v-if="!editing"
            v-html="comment.content"
        ></p>
        <div v-else>
            <comment-form
                :logged-in="loggedIn"
                :prop-content="comment.content"
                :api-url="apiUrl"
                @submitted="editComment"
                @error="notifyError('Error editing comment.')"
                @cancel="cancelEditing"
            />
        </div>
        <span :class="{'text-gold' : comment.liked}">
            <font-awesome-icon
                class="hover-icon c-pointer align-middle"
                icon="thumbs-up"
                transform="grow-1.5"
                @click="likeComment"
            />
            <span class="ml-1">{{ comment.likeCount }} {{ $t('post.likes') }}</span>
        </span>
    </div>
</template>

<script>
import CommentForm from './CommentForm';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash, faThumbsUp} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {NotificationMixin} from '../../mixins';
import ConfirmModal from '../modal/ConfirmModal';

library.add(faEdit);
library.add(faTrash);
library.add(faThumbsUp);

export default {
    name: 'Comment',
    mixins: [
        NotificationMixin,
    ],
    components: {
        FontAwesomeIcon,
        CommentForm,
        ConfirmModal,
    },
    props: {
        comment: Object,
        index: Number,
        loggedIn: Boolean,
    },
    data() {
        return {
            deleteDisabled: false,
            editing: false,
            liking: false,
            isConfirmVisible: false,
        };
    },
    computed: {
        date() {
            return moment(this.comment.createdAt).format('H:mm, MMM D, YYYY');
        },
        apiUrl() {
            return this.$routing.generate('edit_comment', {commentId: this.comment.id});
        },
        profileUrl() {
            return this.$routing.generate('profile-view', {nickname: this.comment.author.profile.nickname});
        },
    },
    methods: {
        deleteComment() {
            this.deleteDisabled = true;
            this.$axios.single.post(this.$routing.generate('delete_comment', {commentId: this.comment.id}))
                .then((res) => {
                    this.$emit('delete-comment', this.index);
                    this.notifySuccess(res.data.message);
                })
                .catch(() => {
                    this.notifyError('Error deleting comment.');
                })
                .finally(() => {
                    this.deleteDisabled = false;
                });
        },
        editComment(comment) {
            this.$emit('update-comment', {...this.comment, content: comment.content});
            this.cancelEditing();
        },
        cancelEditing() {
            this.editing = false;
        },
        likeComment() {
            if (!this.loggedIn) {
                location.href = this.$routing.generate('login', {}, true);
                return;
            }
            if (this.liking) {
                return;
            }
            this.liking = true;
            this.$axios.single.post(this.$routing.generate('like_comment', {commentId: this.comment.id}))
                .then(() => {
                    this.$emit('update-comment', {
                        ...this.comment,
                        likeCount: this.comment.likeCount + (this.comment.liked ? -1 : 1),
                        liked: !this.comment.liked,
                    });
                })
                .catch(() => {
                    this.notifyError('Error liking comment.');
                })
                .finally(() => this.liking = false);
        },
        showConfirm() {
            this.isConfirmVisible = true;
        },
        closeConfirm() {
            this.isConfirmVisible = false;
        },
    },
};
</script>
