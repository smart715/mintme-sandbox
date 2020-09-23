import AOS from 'aos';
import NewsImage from './components/NewsImage';
import store from './storage';

AOS.init({
    easing: 'ease-out-back',
    duration: 1600,
    once: true,
});

new Vue({
    el: '#news-selector',
    store,
    components: {NewsImage},
});
