import '../../scss/pages/edit_post.sass';
import EditPost from '../components/posts/EditPost';
import i18n from '../utils/i18n/i18n';

new Vue({
    el: '#edit_post',
    i18n,
    components: {
        EditPost,
    },
});
