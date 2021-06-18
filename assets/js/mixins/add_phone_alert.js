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
            addPhoneModalProfileNickName: '',
        };
    },
    computed: {
        addPhoneModalMessage: function() {
            return this.$t('modal.add_phone_alert', {
                profileUrl: this.$routing.generate('profile-view', {
                    nickname: this.addPhoneModalProfileNickName,
                    edit: 1,
                }),
                extraAttributes: this.embeded ? 'target="_blank"' : '',
                messageType: this.$t(`modal.add_phone_alert_${this.addPhoneModalMessageType}.message`),
            });
        },
    },
};
