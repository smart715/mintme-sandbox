import Post from './components/posts/Post';
import Comments from "./components/posts/Comments";

new Vue({
    el: '#show_post',
    components: {
        Comments,
        Post,
    },
});
