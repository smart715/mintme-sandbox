import '../../scss/pages/chat.sass';
import ChatWidget from '../../../assets/js/components/chat/ChatWidget';
import store from '../storage';
import i18n from '../utils/i18n/i18n';

new Vue({
    el: '#chat',
    components: {
        ChatWidget,
    },
    i18n,
    store,
});
