import RecentPosts from './components/posts/RecentPosts';
import i18n from './utils/i18n/i18n';

new Vue({
    el: '#recent_posts',
    i18n,
    components: {
        RecentPosts,
    },
});
