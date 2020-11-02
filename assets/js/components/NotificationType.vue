<template>
    <div class="notification-type">
        <p
            v-if="notification.type !== notificationType.filled && notification.type !== notificationType.cancelled"
            v-html="this.$t(notification.type + '.msg', this.translationsContext(notification))">
        </p>
        <p
            v-if="notification.type === notificationType.filled || notification.type === notificationType.cancelled"
            v-html="this.$t('no_orders.msg', this.translationsContext(notification))">
        </p>
    </div>
</template>

<script>
import {notificationTypes} from '../utils/constants';

export default {
    name: 'NotificationType',
    props: {
        notification: Object,
    },
    data() {
        return {
            notificationType: notificationTypes,
        };
    },
    methods: {
        translationsContext: function(notification) {
            if (this.notificationType.withdrawal !== notification.type && this.notificationType.deposit !== notification.type) {
                return {
                    urlProfile: this.$routing.generate('profile-view', {nickname: notification.jsonData.profile}),
                    profile: notification.jsonData.profile,
                    tokenName: notification.jsonData.tokenName,
                    urlToken: this.$routing.generate('token_show', {name: notification.jsonData.tokenName}),
                };
            }
            return {
                urlWallet: this.$routing.generate('wallet', {tab: 'dw-history'}),
            };
        },
    },
};
</script>
