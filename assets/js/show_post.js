import Post from './components/posts/Post';
import Comments from './components/posts/Comments';
import i18n from './utils/i18n/i18n';
import store from './storage';

new Vue({
    el: '#show_post',
    i18n,
    store,
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
