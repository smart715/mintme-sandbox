<template>
    <div class="comments">
        <comment-form
            :logged-in="loggedIn"
            :api-url="apiUrl"
            @submitted="$emit('new-comment', $event)"
            @error="notifyError('Error creating comment.')"
            reset-after-submit
        />
        <div class="my-3">
            <template v-if="commentsCount > 0">
                <comment
                    v-for="(n, i) in commentsCount"
                    :comment="comments[i]"
                    :key="i"
                    :index="i"
                    :logged-in="loggedIn"
                    @delete-comment="$emit('delete-comment', $event)"
                />
            </template>
            <div v-else class="text-center w-100">
                No one commented yet.
            </div>
        </div>
    </div>
</template>

<script>
import Comment from './Comment';
import CommentForm from './CommentForm';
import {NotificationMixin} from '../../mixins';

export default {
    name: 'Comments',
    mixins: [
        NotificationMixin,
    ],
    components: {
        Comment,
        CommentForm,
    },
    props: {
        comments: Array,
        postId: Number,
        loggedIn: Boolean,
    },
    data() {
        return {
        };
    },
    computed: {
        commentsCount() {
            return this.comments.length;
        },
        apiUrl() {
            return this.$routing.generate('add_comment', {id: this.postId});
        },
    },
};
</script>
