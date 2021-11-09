export default {
    methods: {
        sendLogs: function(level, message, data) {
            if (data instanceof Error) {
                let error = {};
                Object.getOwnPropertyNames(data).forEach((key) => {
                    error[key] = data[key];
                });
                data = error;
            }
            return this.$axios.retry.post(this.$routing.generate('log'), {
                level: level,
                message: message,
                context: JSON.stringify(data),
            });
        },
    },
};
