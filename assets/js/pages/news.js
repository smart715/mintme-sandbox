import '../../scss/pages/news.sass';
import AOS from 'aos';
import NewsImage from '../components/NewsImage';
import store from '../storage';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faSquareXTwitter, faRedditSquare, faFacebookSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';

library.add(faSquareXTwitter, faRedditSquare, faFacebookSquare);

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
