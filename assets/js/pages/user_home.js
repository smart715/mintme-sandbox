import '../../scss/pages/user_home.sass';
import RecentPosts from '../components/posts/RecentPosts';
import i18n from '../utils/i18n/i18n';

new Vue({
    el: '#user-home',
    i18n,
    components: {
        RecentPosts,
    },
});
