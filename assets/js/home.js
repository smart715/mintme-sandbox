import Typed from 'typed.js';
import Feed from './components/Feed';
import i18n from './utils/i18n/i18n';

new Vue({
    el: '#home',
    i18n,
    components: {
        Feed,
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
