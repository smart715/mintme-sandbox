export default {
    methods: {
        sendLogs: function(level, message, data, dummy = null) {
            this.$axios.retry.post(this.$routing.generate('log'), {
                level: level,
                message: message,
                context: JSON.stringify({
                    'user-amount': data,
                    'dummy-value': dummy,
                }),
            });
        },
    },
};
