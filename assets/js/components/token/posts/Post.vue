<template>
    <div class="post">
        <p v-if="post.content">
            {{ post.content }}
        </p>
        <p v-else>
            To see this post you need to have {{post.amount}} {{post.token.name}} in your balance. Visit trade page and create buy order to get required tokens.
        </p>
        <span>{{ post.createdAt }}</span>
        <span>{{ post.author.name }}</span>
    </div>
</template>

<script>
export default {
    name: 'Post',
    props: {
        post: Object,
    },
    data() {
        return {};
    },
};
</script>
