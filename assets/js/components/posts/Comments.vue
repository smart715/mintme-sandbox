<template>
    <div class="comments">
        <textarea
            class="form-control mb-3"
            v-model="newComment"
            @focus="goToLogIn"
        ></textarea>
        <button
            class="btn btn-primary"
            @click="addComment"
        >
            Save
        </button>
        <button class="btn btn-cancel">
            Cancel
        </button>
        <div class="my-3">
            <template v-if="commentsCount > 0">
                <comment
                    v-for="(n, i) in commentsCount"
                    :comment="comments[i]"
                    :key="i"
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
        };
    },
    computed: {
        commentsCount() {
            return this.comments.length;
        },
    },
    methods: {
        addComment() {
            if (!this.loggedIn) {
                location.href = this.$routing.generate('login', {}, true);
                return;
            }

            this.$axios.single.post(this.$routing.generate('add_comment', {id: this.postId}), {
                content: this.newComment,
            });
        },
        goToLogIn(e) {
            if (!this.loggedIn) {
                e.target.blur();
                location.href = this.$routing.generate('login', {}, true);
            }
        },
    },
};
</script>