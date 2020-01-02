export default {
    methods: {
        sendLogs: function(message, data) {
            this.$axios.retry.post(
                this.$routing.generate('send_logs'),
                {
                    msg: message,
                    data: data,
                }
            );
        },
    },
};
