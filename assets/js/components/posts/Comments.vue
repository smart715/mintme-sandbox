<template>
    <div class="comments">
        <div class="form-group">
            <textarea
                class="form-control mb-3"
                :class="{ 'is-invalid' : newCommentInvalid }"
                v-model="newComment"
                @focus="goToLogIn"
            ></textarea>
            <div class="invalid-feedback">
                {{ newCommentError }}
            </div>
        </div>
        <button
            class="btn btn-primary"
            @click="addComment"
        >
            Save
        </button>
        <button
            class="btn btn-cancel"
            @click="cancel"
        >
            Cancel
        </button>
        <div class="my-3">
            <template v-if="commentsCount > 0">
                <comment
                    v-for="(n, i) in commentsCount"
                    :comment="comments[i]"
                    :key="i"
                    :index="i"
                    :logged-in="loggedIn"
                    @delete-comment="$emit('delete-comment', $event)"
                ></comment>
            </template>
            <div v-else class="text-center w-100">
                No one commented yet.
            </div>
        </div>
    </div>
</template>

<script>
import Comment from './Comment';
import {required, minLength, maxLength} from 'vuelidate/lib/validators';

export default {
    name: 'Comments',
    components: {
        Comment,
    },
    props: {
        comments: Array,
        postId: Number,
        loggedIn: Boolean,
    },
    data() {
        return {
            newComment: '',
            loginUrl: this.$routing.generate('login', {}, true),
            minContentLength: 1,
            maxContentLength: 500,
        };
    },
    computed: {
        commentsCount() {
            return this.comments.length;
        },
        newCommentInvalid() {
            return this.$v.newComment.$invalid && this.newComment.length > 0;
        },
        newCommentError() {
            if (!this.$v.newComment.required) {
                return 'Content can\'t be empty';
            }
            if (!this.$v.newComment.minLength) {
                return `Content must be at least ${this.minContentLength} characters long`;
            }
            if (!this.$v.newComment.maxLength) {
                return `Content can't be more than ${this.maxContentLength} characters long`;
            }
            return '';
        },
    },
    methods: {
        addComment() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
                return;
            }

            if (this.$v.newComment.$invalid) {
                return;
            }

            this.$axios.single.post(this.$routing.generate('add_comment', {id: this.postId}), {
                content: this.newComment,
            }).then((res) => {
                this.$emit('new-comment', res.data.comment);
                this.newComment = '';
            });
        },
        goToLogIn(e) {
            if (!this.loggedIn) {
                e.target.blur();
                location.href = this.loginUrl;
            }
        },
        cancel() {
            if (!this.loggedIn) {
                location.href = this.loginUrl;
            }

            this.newComment = '';
        },
    },
    validations() {
        return {
            newComment: {
                required,
                minLength: minLength(this.minContentLength),
                maxLength: maxLength(this.maxContentLength),
            };
        };
    },
};
</script>
