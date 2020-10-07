import AOS from 'aos';
import NewsImage from './components/NewsImage';
import store from './storage';


if (document.getElementById('news')) {
    new Vue({
        el: '#news',
        components: {
            FontAwesomeIcon,
            FontAwesomeLayers,
        },
    });
}

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
