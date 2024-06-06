import i18n from './utils/i18n/i18n';
import LocaleSwitcher from './components/LocaleSwitcher';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCookieBite} from '@fortawesome/free-solid-svg-icons';
import {
    faDiscord,
    faRedditAlien,
    faXTwitter,
    faTelegramPlane,
    faFacebookF,
    faInstagram,
} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {OpenPageMixin} from './mixins';

library.add(
    faCookieBite,
    faRedditAlien,
    faXTwitter,
    faTelegramPlane,
    faDiscord,
    faFacebookF,
    faInstagram,
);

if (document.getElementById('footer')) {
    new Vue({
        el: '#footer',
        i18n,
        components: {
            FontAwesomeIcon,
            LocaleSwitcher,
        },
        mixins: [
            OpenPageMixin,
        ],
    });
}
