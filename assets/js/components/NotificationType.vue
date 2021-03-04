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
import {tabs} from '../utils/constants';

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
                this.notificationTypes.deployed,
                this.notificationTypes.cancelled,
                this.notificationTypes.filled,
                ].includes(notification.type)) {
                let jsonData = JSON.parse(notification.jsonData);
                return {
                    tokenName: jsonData.tokenName,
                    urlToken: this.$routing.generate('token_show', {name: jsonData.tokenName}),
                };
            }

            if (this.notificationTypes.newPost === notification.type) {
                let jsonData = JSON.parse(notification.jsonData);
                return {
                  tokenName: jsonData.tokenName,
                  urlToken: jsonData.hasOwnProperty('slug')
                      ? this.$routing.generate('new_show_post', {tokenName: jsonData.tokenName, slug: jsonData.slug})
                      : this.$routing.generate('token_show', {name: jsonData.tokenName, tab: tabs[2]}),
                };
            }

            if (this.notificationTypes.newInvestor === notification.type) {
                let jsonData = JSON.parse(notification.jsonData);
                return {
                    profile: jsonData.profile,
                    urlProfile: this.$routing.generate('profile-view', {nickname: jsonData.profile}),
                    urlToken: this.$routing.generate('token_show', {name: jsonData.tokenName}),
                };
            }

            if (this.notificationTypes.tokenMarketingTips === notification.type) {
                let jsonData = JSON.parse(notification.jsonData);
                return {
                    url: this.$routing.generate('kb_show', {url: jsonData.kbLink}),
                    title: this.kbTitle(jsonData.kbLink),
                };
            }

            if ([this.notificationTypes.deposit, this.notificationTypes.withdrawal].includes(notification.type)) {
                return {
                    urlWallet: this.$routing.generate('wallet', {tab: 'dw-history'}),
                };
            }
        },
        kbTitle: function(kbLink) {
            return kbLink.split('-').join(' ');
        },
    },
};
</script>
