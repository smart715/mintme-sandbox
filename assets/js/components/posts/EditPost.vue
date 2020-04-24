<template>
    <post-form
        :api-url="$routing.generate('edit_post', {id: post.id})"
        :post="post"
    />
</template>

<script>
import PostForm from './PostForm';

export default {
    name: 'EditPost',
    components: {
        PostForm
    },
    props: {
        post: {
            type: Object,
            required: true,
        }
    }
}
</script>