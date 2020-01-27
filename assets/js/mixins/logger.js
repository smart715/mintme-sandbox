export default {
    methods: {
        sendLogs: function(level, message, data) {
            this.$axios.retry.post(this.$routing.generate('log'), {
                level: level,
                message: message,
                context: JSON.stringify(data),
            });
        },
    },
};
