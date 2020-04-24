<template>
    <post-form
        :api-url="$routing.generate('create_post')"
    />
</template>

<script>
import PostForm from './PostForm';

export default {
    name: 'CreatePost',
    components: {
        PostForm
    },
}
</script>