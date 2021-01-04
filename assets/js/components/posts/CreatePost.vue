<template>
    <post-form
        :token-name="tokenName"
        :subunit="subunit"
        @save-success="$emit('update-posts')"
        reset-after-action
        reset-cancel
    />
</template>

<script>
import PostForm from './PostForm';

export default {
    name: 'CreatePost',
    components: {
        PostForm,
    },
    props: {
        tokenName: String,
        subunit: Number,
    },
};
</script>
