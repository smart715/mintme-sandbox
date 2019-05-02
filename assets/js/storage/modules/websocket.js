import {w3cwebsocket as W3CWebSocket} from 'websocket';

export const status = {
    FAILED: 1,
    PENDING: 2,
    SUCCESS: 3,
};

const storage = {
    namespaced: true,
    state: {
        clients: {},
    },
    getters: {
        getClient: (state) => (url) => {
            return state.clients[url];
        },
    },
    actions: {
        addMessageHandler(context, {url, id, handler}) {
            context.commit('init', url);
            context.commit('addMessageHandler', {url, id, handler});
        },
        addOnOpenHandler(context, {url, handler}) {
            context.commit('init', url);
            context.commit('addOnOpenHandler', {url, handler});
        },
        sendMessage(context, {url, request}) {
            context.commit('init', url);
            context.commit('sendMessage', {url, request});
        },
        authorize(context, url) {
            context.commit('init', url);
            context.commit('auth', {url, status: status.SUCCESS});
        },
        login(context, url) {
            context.commit('init', url);
            context.commit('auth', {url, status: status.PENDING});
        },
        logout(context, url) {
            context.commit('init', url);
            context.commit('auth', {url, status: status.FAILED});
        },
        init(context, url) {
            context.commit('init', url);
        },
    },
    mutations: {
        init(state, url) {
            if (!state.clients.hasOwnProperty(url)) {
                state.clients[url] = {ws: undefined, auth: status.FAILED, handlers: {
                    message: [], open: [],
                }};
                state.clients[url].ws = new W3CWebSocket(url);
            }
        },
        addMessageHandler(state, {url, id, handler}) {
            if (!state.clients[url].handlers.message.includes(handler)) {
                if (id) {
                    state.clients[url].handlers.message.forEach((el, i) => {
                        if (Array.isArray(el) && el[0] === id) {
                            state.clients[url].handlers.message.splice(i, 1);
                        }
                    });
                    state.clients[url].handlers.message.push([id, handler]);
                } else {
                    state.clients[url].handlers.message.push(handler);
                }
            }

            state.clients[url].ws.onmessage = function(result) {
                result = typeof result.data === 'string' ? JSON.parse(result.data): result;
                state.clients[url].handlers.message.forEach((handler) => {
                    if (Array.isArray(handler)) {
                        handler[1](result);
                    } else {
                        handler(result);
                    }
                });
            };
        },
        addOnOpenHandler(state, {url, handler}) {
            if (state.clients[url].handlers.open.includes(handler)) {
                return;
            }

            state.clients[url].handlers.open.push(handler);

            if (state.clients[url].ws.readyState === state.clients[url].ws.OPEN) {
                handler();
            } else {
                state.clients[url].ws.onopen = function() {
                    state.clients[url].handlers.open.forEach((handler) => handler());
                };
            }
        },
        sendMessage(state, {url, request}) {
            state.clients[url].ws.send(request);
        },
        auth(state, {url, status}) {
            state.clients[url].auth = status;
        },
    },
};

export default storage;
