export default {
    data() {
        return {
            addPhoneModalVisible: false,
            addPhoneModalMessageType: '',
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
                messageType: this.$t('modal.add_phone_alert_' + this.addPhoneModalMessageType + '.message'),
            });
        },
    },
    };
