export default {
    props: {
        embeded: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            setTwoFactorModalVisible: false,
            setTwoFactorModalMessageType: 'action',
        };
    },
    computed: {
        setTwoFactorModalMessage: function() {
            return this.$t('modal.set_two_factor_alert', {
                messageType: this.$t(`modal.set_two_factor_alert_${this.setTwoFactorModalMessageType}.message`),
            });
        },
    },
};
