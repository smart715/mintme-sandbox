import NewsImage from './components/NewsImage';
import store from './storage';

new Vue({
    el: '#news-selector',
    store,
    components: {NewsImage},
});

