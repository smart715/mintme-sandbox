import '../../scss/pages/trading.sass';
import Trading from '../components/trading/Trading';
import store from '../storage';
import i18n from '../utils/i18n/i18n';
import CryptoInit from '../components/CryptoInit';

new Vue({
    el: '#trading',
    i18n,
    components: {
        Trading,
        CryptoInit,
    },
    data() {
        return {
            isPageReady: false,
        };
    },
    store,
});

new Vue({
    el: '#buy-mintme-button',
    methods: {
        scrollToTop() {
            window.scrollTo(0, 0);
        },
    },
});
