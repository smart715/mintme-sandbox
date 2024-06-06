import '../../scss/pages/coin.sass';
import i18n from '../utils/i18n/i18n';
import AOS from 'aos';
import Intro from '../components/coin/Intro';
import KeyFacts from '../components/coin/KeyFacts';
import RoadMap from '../components/coin/RoadMap';
import 'aos/dist/aos.css';

AOS.init({
    easing: 'ease-out-back',
    duration: 1600,
    once: true,
});


new Vue({
    el: '#coin',
    i18n,
    components: {
        Intro,
        KeyFacts,
        RoadMap,
    },
});
