<template>
    <post-form
        :api-url="$routing.generate('create_post')"
        @save-success="$emit('update-posts')"
        reset-after-action
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
