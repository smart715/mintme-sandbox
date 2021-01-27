<template>
    <post-form
        :post="post"
        :subunit="subunit"
        @save-success="redirect"
        @cancel="redirect"
    >
        <template v-slot:title>{{ $t('edit_post') }}</template>
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
        subunit: Number,
    },
    methods: {
        redirect() {
            location.href = this.$routing.generate('token_show', {name: this.post.token.name, tab: 'posts'});
        },
    },
};
</script>
