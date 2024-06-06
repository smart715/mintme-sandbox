export default {
    install(Vue, options) {
        Vue.prototype.$goBack = function(e) {
            if (e && ('object' === typeof e) && ('preventDefault' in e)) e.preventDefault();
            1 < window.history.length
                ? window.history.back()
                : window.location.href = '/';
        };
    },
};
