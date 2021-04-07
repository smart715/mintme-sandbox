import VueClipboard from 'vue-clipboard2';
import VueTippy from 'vue-tippy';
import Vuelidate from 'vuelidate';
import sanitizeHtml from './sanitize_html';
import store from './storage';
import UserInit from './components/UserInit';
import moment from 'moment';

Vue.prototype.moment = moment;

/*
    To enable passive listeners,
    look https://developers.google.com/web/tools/lighthouse/audits/passive-event-listeners
    Temporary disabled due the chart conflicts
*/
// import 'default-passive-events';

VueClipboard.config.autoSetContainer = true;

Vue.use(VueClipboard);
Vue.use(Vuelidate);
Vue.use(sanitizeHtml);

Vue.use(VueTippy, {
    directive: 'tippy',
    flipDuration: 0,
    popperOptions: {
        modifiers: {
            preventOverflow: {
                boundariesElement: 'window',
            },
        },
    },
});

Vue.options.delimiters = ['{[', ']}'];

const imagesContext = require.context(
    '../img',
    false,
    /\.(png|jpg|jpeg|gif|ico|svg)$/
);
imagesContext.keys().forEach(imagesContext);

new Vue({
    el: '#user-init',
    components: {
        UserInit,
    },
    store,
});
