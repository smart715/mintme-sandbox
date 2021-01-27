export default {
    install(Vue, options) {
        Vue.prototype.$goBack = function(e) {
            if (e && (typeof e === 'object') && ('preventDefault' in e)) e.preventDefault();
            window.history.length > 1
                ? window.history.back()
                : window.location.href = '/';
        };
    },
};
