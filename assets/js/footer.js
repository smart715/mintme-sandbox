import i18n from './utils/i18n/i18n';
import LocaleSwitcher from './components/LocaleSwitcher';
import CurrencyModeSwitcher from './components/CurrencyModeSwitcher';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';

new Vue({
    el: '#footer',
    i18n,
    components: {
        FontAwesomeIcon,
        FontAwesomeLayers,
        LocaleSwitcher,
        CurrencyModeSwitcher,
    },
});

