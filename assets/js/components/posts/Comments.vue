<template>
    <div class="comments">
        <textarea class="form-control mb-3" v-model="newComment"></textarea>
        <button class="btn btn-primary" @click="addComment">
            Save
        </button>
        <button class="btn btn-cancel">
            Cancel
        </button>
        <div class="mt-3">
            <comment v-for="(n, i) in commentsCount"
                 :comment="comments[i]"
                 :key="i"
            />
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
            this.$axios.single.post(this.$routing.generate('add_comment', {id: this.postId}), {
                content: this.newComment,
            });
        },
    },
};
</script>