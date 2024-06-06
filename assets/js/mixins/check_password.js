import NotificationMixin from './notification';

export default {
    mixins: [NotificationMixin],
    methods: {
        isPasswordEqualToSavedPassword: async function(password, token='') {
            try {
                const response = await this.$axios.single.post(this.$routing.generate('check_password_duplicate'), {
                    password: password,
                    token: token,
                });
                return response.data.isDuplicate;
            } catch (err) {
                this.$logger.error(this.$t('toasted.error.can_not_connect'), err);
                this.notifyError(this.$t('toasted.error.can_not_connect'));
            }
        },
    },
};
