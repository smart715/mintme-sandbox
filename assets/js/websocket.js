import {w3cwebsocket as W3CWebSocket} from 'websocket';

export default {
    install(Vue, options) {
        Vue.prototype.$socket = (url) => {
            const wsUrl = url || options.url || '/';
            return new W3CWebSocket(wsUrl);
        };
    },
};
