<template>
    <post-form
        :api-url="$routing.generate('edit_post')"
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