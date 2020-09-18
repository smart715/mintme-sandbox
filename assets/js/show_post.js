\import Post from './components/posts/Post';
import Comments from './components/posts/Comments';

new Vue({
    el: '#show_post',
    components: {
        Comments,
        Post,
    },
    data() {
        return {
            comments: null,
        };
    },
    methods: {
        deleteComment(index) {
            this.comments.splice(index, 1);
        },
        newComment(comment) {
            this.comments.unshift(comment);
        },
    },
});
