<template>
    <div class="notification-type">
        <p
            v-if="notification.type !== notificationTypes.filled && notification.type !== notificationTypes.cancelled"
            v-html="this.$t(notification.type + '.msg', this.translationsContext(notification))">
        </p>
        <p
            v-if="notification.type === notificationTypes.filled || notification.type === notificationTypes.cancelled"
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
            notificationTypes,
        };
    },
    methods: {

        translationsContext: function(notification) {
            if ([
                this.notificationTypes.withdrawal,
                this.notificationTypes.deposit,
                this.notificationTypes.tokenMarketingTips].includes(notification.type)) {
                let jsonData = JSON.parse(notification.jsonData);
                return {
                    urlProfile: this.$routing.generate('profile-view', {nickname: jsonData.profile}),
                    profile: jsonData.profile,
                    tokenName: jsonData.tokenName,
                    urlToken: this.$routing.generate('token_show', {name: jsonData.tokenName}),
                    url: this.$routing.generate('kb_show', {url: jsonData.kbLink}),
                    title: this.kbTitle(jsonData.kbLink),
                };
            }
            return {
                urlWallet: this.$routing.generate('wallet', {tab: 'dw-history'}),
            };
        },
        kbTitle: function(kbLink) {
            return kbLink.split('-').join(' ');
        },
    },
};
</script>
