export default {
    install(Vue, options) {
        /**
         *
         * @param {string} level
         * @param {string} message
         * @param {object} data
         * @return {unresolved}
         */
        function logger(level, message, data) {
            if (data instanceof Error) {
                const error = {};
                Object.getOwnPropertyNames(data).forEach((key) => {
                    error[key] = data[key];
                });
                data = error;
            }
            return Vue.prototype.$axios.retry.post(Vue.prototype.$routing.generate('log'), {
                level: level,
                message: message,
                context: JSON.stringify(data),
            });
        };
        Vue.prototype.$logger = {
            error(message, data) {
                logger('error', message, data);
            },
            success(message, data) {
                logger('success', message, data);
            },
            logger,
        };
    },
};
