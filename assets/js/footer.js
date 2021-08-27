import i18n from './utils/i18n/i18n';
import LocaleSwitcher from './components/LocaleSwitcher';
import CurrencyModeSwitcher from './components/CurrencyModeSwitcher';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCookieBite} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

library.add(faCookieBite);

if (document.getElementById('footer')) {
    new Vue({
        el: '#footer',
        i18n,
        components: {
            FontAwesomeIcon,
            LocaleSwitcher,
            CurrencyModeSwitcher,
        },
    });
}
