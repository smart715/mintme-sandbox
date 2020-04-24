<template>
    <post-form
        :api-url="$routing.generate('create_post')"
        @save-success="$emit('update-posts')"
    />
</template>

<script>
import PostForm from './PostForm';

export default {
    name: 'CreatePost',
    components: {
        PostForm,
    },
};
</script>
