<template>
    <post-form
        :api-url="$routing.generate('edit_post', {id: post.id})"
        :post="post"
        @save-success="redirect"
        @cancel="redirect"
    >
        <template v-slot:title>Edit Post</template>
    </post-form>
</template>

<script>
import PostForm from './PostForm';

export default {
    name: 'EditPost',
    components: {
        PostForm,
    },
    props: {
        post: {
            type: Object,
            required: true,
        },
    },
    methods: {
        redirect() {
            location.href = this.$routing.generate('token_show', {name: this.post.token.name, tab: 'posts'});
        },
    },
};
</script>
