export default {
    methods: {
        sendLogs: function(level, message, data) {
            return this.$axios.retry.post(this.$routing.generate('log'), {
                level: level,
                message: message,
                context: JSON.stringify(data),
            });
        },
    },
};
