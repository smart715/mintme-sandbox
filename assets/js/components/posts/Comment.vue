<template>
    <div class="comment">
        <div>
            <a :href="$routing.generate('profile-view', {nickname: comment.author.profile.nickname})" class="text-white">
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
            <template v-if="comment.editable">
                <button
                    class="btn btn-link p-0 delete-icon float-right text-decoration-none text-reset"
                    :disabled="deleteDisabled"
                    @click="deleteComment"
                >
                    <font-awesome-icon
                        class="icon-default c-pointer align-middle"
                        icon="trash"
                        transform="shrink-4 up-1.5"
                    />
                </button>
                <button
                    class="btn btn-link p-0 post-edit-icon float-right text-decoration-none text-reset"
                    @click="editing = true"
                >
                    <font-awesome-icon
                        class="icon-default c-pointer align-middle"
                        icon="edit"
                        transform="shrink-4 up-1.5"
                    />
                </button>
            </template>
        </div>
        <p v-if="!editing"
            v-html="comment.content"
        ></p>
        <div v-else>
            <textarea
                class="form-control my-3"
                v-model="newContent"
            ></textarea>
            <button
                class="btn btn-primary"
                @click="editComment"
            >
                Save
            </button>
            <button class="btn btn-cancel"
                @click="cancelEditing"
            >
                Cancel
            </button>
        </div>
        <span>
            <font-awesome-icon
                class="icon-default c-pointer align-middle"
                icon="thumbs-up"
                transform="shrink-4 up-1.5"
                @click="likeComment"
            />
            {{ comment.likeCount }}
        </span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTrash, faThumbsUp} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {NotificationMixin} from '../../mixins';

library.add(faEdit);
library.add(faTrash);
library.add(faThumbsUp);

export default {
    name: 'Comment',
    mixins: [
        NotificationMixin
    ],
    components: {
        FontAwesomeIcon,
    },
    props: {
        comment: Object,
        index: Number,
    },
    data() {
        return {
            deleteDisabled: false,
            editing: false,
            newContent: this.comment.content,
        };
    },
    computed: {
        date() {
            return moment(this.comment.createdAt).format('H:mm, MMM D, YYYY');
        },
    },
    methods: {
        deleteComment() {
            this.deleteDisabled = true;
            this.$axios.single.post(this.$routing.generate('delete_comment', {id: this.comment.id}))
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
        editComment() {
            this.$axios.single.post(this.$routing.generate('edit_comment', {id: this.comment.id}), {
                content: this.newContent,
            }).then((res) => {
                this.comment.content = res.data.comment.content;
                this.newContent = res.data.comment.content;
                this.editing = false;
            });
        },
        cancelEditing() {
            this.newContent = this.comment.content;
            this.editing = false;
        },
        likeComment() {
            this.$axios.single.post(this.$routing.generate('like_comment', {id: this.comment.id}))
                .then((res => {
                    console.log(res.data.message);
                }));
        },
    },
};
</script>
