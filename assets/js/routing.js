export default {
    install(Vue, options) {
        Vue.prototype.$routing = window.Routing;
    },
};
