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
    data() {
        return {
            posts: [
                {
                    content: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rerum quam eum, odio rem beatae aliquam voluptatem iste consequuntur esse maxime, aspernatur delectus similique amet! Laudantium, quis impedit ipsum tenetur ipsam!',
                    createdAt: '09.04.2020',
                    author: 'alejandrojsn',
                },
                {
                    content: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rerum quam eum, odio rem beatae aliquam voluptatem iste consequuntur esse maxime, aspernatur delectus similique amet! Laudantium, quis impedit ipsum tenetur ipsam!',
                    createdAt: '09.04.2020',
                    author: 'alejandrojsn',
                },
                {
                    content: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rerum quam eum, odio rem beatae aliquam voluptatem iste consequuntur esse maxime, aspernatur delectus similique amet! Laudantium, quis impedit ipsum tenetur ipsam!',
                    createdAt: '09.04.2020',
                    author: 'alejandrojsn',
                },
                {
                    content: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Rerum quam eum, odio rem beatae aliquam voluptatem iste consequuntur esse maxime, aspernatur delectus similique amet! Laudantium, quis impedit ipsum tenetur ipsam!',
                    createdAt: '09.04.2020',
                    author: 'alejandrojsn',
                },
            ],
        };
    },
};
</script>
