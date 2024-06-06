export default {
    props: {
        embeded: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            addPhoneModalVisible: false,
            addPhoneModalMessageType: 'action',
        };
    },
    computed: {
        addPhoneModalMessage: function() {
            return this.$t('modal.add_phone_alert', {
                messageType: this.$t(`modal.add_phone_alert_${this.addPhoneModalMessageType}.message`),
            });
        },
    },
};
