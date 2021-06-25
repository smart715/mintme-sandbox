import '../../scss/pages/home.sass';
import Typed from 'typed.js';
import Feed from '../components/Feed';
import Countdown from '../components/Countdown.vue';
import FaqItem from '../components/FaqItem';
import i18n from '../utils/i18n/i18n';
import '../../img/hero-image.webp';

new Vue({
    el: '#home',
    i18n,
    components: {
        Feed,
        Countdown,
        FaqItem,
    },
});

new Typed('#typed', {
    stringsElement: '#typed-strings',
    typeSpeed: 100,
    backSpeed: 100,
    loop: true,
    showCursor: true,
    cursorChar: '|',
    backDelay: 2500,
});
