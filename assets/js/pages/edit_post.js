import EditPost from '../components/posts/EditPost';
import store from '../storage';
import i18n from '../utils/i18n/i18n';

new Vue({
    el: '#edit_post',
    i18n,
    store,
    components: {
        EditPost,
    },
});
