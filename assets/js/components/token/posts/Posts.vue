<template>
    <div class="card h-100">
        <div class="card-header">
            <slot name="title">Posts</slot>
        </div>
        <div class="card-body posts">
            <post v-for="(post, key) in posts" :post="post" :key="key"></post>
        </div>
    </div>
</template>

<script>
import Post from './Post';

export default {
    name: 'Posts',
    components: {Post},
    props: {
        posts: Array,
    },
    data() {
        return {
        };
    },
};
</script>
