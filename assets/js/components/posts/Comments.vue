<template>
    <div class="comments">
        <textarea class="form-control"></textarea>
        <button class="btn btn-primary">
            Save
        </button>
        <button class="btn btn-cancel">
            Cancel
        </button>
        <comment v-for="(n, i) in commentsCount"
             :comment="comments[i]"
             :key="i"
        />
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
    },
    computed: {
        commentsCount() {
            return this.comments.length;
        },
    },
};
</script>