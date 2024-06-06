<template>
    <div class="notification-type">
        <p
            v-if="notification.type === notificationTypes.broadcast"
            v-html="notification[this.currentLocale + 'Content']"
        ></p>
        <p
            v-else-if="[notificationTypes.filled, notificationTypes.cancelled].includes(notification.type)"
            v-html="this.$t('no_orders.msg', this.translationsContext(notification))"
        ></p>
        <p
            v-else
            v-html="this.$t(notification.type + '.msg', this.translationsContext(notification))"
        ></p>
    </div>
</template>

<script>
import {generateCoinAvatarHtml} from '../utils';
import {MINTME, notificationTypes, TOKEN_NAME_TRUNCATE_LENGTH} from '../utils/constants';
import {RebrandingFilterMixin} from '../mixins';
import {TruncateFilterMixin} from '../mixins/filters';

export default {
    name: 'NotificationType',
    mixins: [
        RebrandingFilterMixin,
        TruncateFilterMixin,
    ],
    props: {
        notification: Object,
        currentLocale: String,
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
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    tokenName: jsonData.tokenName,
                    tokenAvatar: generateCoinAvatarHtml({image: jsonData.tokenAvatar, isUserToken: true}),
                    urlToken: this.$routing.generate('token_show_intro', {name: jsonData.tokenName}),
                };
            }

            if (this.notificationTypes.newPost === notification.type) {
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    date: notification.date,
                    tokenName: jsonData.tokenName,
                    tokenAvatar: generateCoinAvatarHtml({image: jsonData.tokenAvatar, isUserToken: true}),
                    urlToken: this.$routing.generate('token_show_post', {name: jsonData.tokenName}),
                    ...this.getGroupedNotificationMeta(notification),
                };
            }

            if (this.notificationTypes.newInvestor === notification.type) {
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    profile: jsonData.profile,
                    profileAvatarUrl: jsonData.profileAvatarUrl,
                    urlProfile: this.$routing.generate('profile-view', {nickname: jsonData.profile}),
                    urlTrade: this.$routing.generate(
                        'token_show_trade',
                        {
                            name: jsonData.tokenName,
                            crypto: this.rebrandingFunc(jsonData.marketSymbol || MINTME.symbol),
                        },
                    ),
                };
            }

            if (this.notificationTypes.tokenMarketingTips === notification.type) {
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    url: this.$routing.generate('kb_show', {url: jsonData.kbLink}),
                    title: this.kbTitle(jsonData.kbLink),
                };
            }

            const transactionTypes = [
                this.notificationTypes.deposit,
                this.notificationTypes.withdrawal,
                this.notificationTypes.transaction_delayed,
            ];

            if (transactionTypes.includes(notification.type)) {
                return {
                    urlWallet: this.$routing.generate('wallet', {tab: 'dw-history'}),
                };
            }

            if (this.isRewardType(notification.type)) {
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    urlRewardFinalize: this.$routing.generate(
                        'token_show_intro',
                        {name: jsonData.rewardToken, modal: 'reward-finalize', slug: jsonData.slug}
                    ),
                    urlRewardSummary: this.$routing.generate(
                        'token_settings',
                        {
                            tokenName: jsonData.rewardToken,
                            tab: 'promotion',
                            modal: 'reward-summary',
                            slug: jsonData.slug,
                        }
                    ),
                    pendingRewardApplication: this.$routing.generate(
                        'token_settings',
                        {
                            tokenName: jsonData.rewardToken,
                            tab: 'promotion',
                        }
                    ),
                    tokenAvatar: generateCoinAvatarHtml({image: jsonData.tokenAvatar, isUserToken: true}),
                    urlRewardToken: this.$routing.generate('token_show_intro', {name: jsonData.rewardToken}),
                    rewardTitleFull: jsonData?.rewardTitle?.length > TOKEN_NAME_TRUNCATE_LENGTH
                        ? jsonData.rewardTitle
                        : '',
                    rewardTitle: this.truncateFunc(jsonData.rewardTitle, TOKEN_NAME_TRUNCATE_LENGTH),
                    rewardTokenFull: jsonData?.rewardToken?.length > TOKEN_NAME_TRUNCATE_LENGTH
                        ? jsonData.rewardToken
                        : '',
                    rewardToken: this.truncateFunc(jsonData.rewardToken, TOKEN_NAME_TRUNCATE_LENGTH),
                    rewardAmount: jsonData.rewardAmount,
                    ownerNickname: jsonData?.ownerNickname,
                    ownerProfileUrl: jsonData?.ownerNickname
                        ? this.$routing.generate('profile-view', {nickname: jsonData.ownerNickname})
                        : null,
                    ...this.getGroupedNotificationMeta(notification),
                };
            }

            if (this.notificationTypes.market_created === notification.type) {
                const jsonData = JSON.parse(notification.jsonData);
                const tokenName = jsonData.tokenName;
                const cryptoSymbol = this.rebrandingFunc(jsonData.cryptoSymbol);
                const tokenAvatar = generateCoinAvatarHtml({image: jsonData.tokenAvatar, isUserToken: true});
                const cryptoAvatar = generateCoinAvatarHtml({
                    image: jsonData.cryptoAvatar,
                    isCrypto: true,
                    symbol: cryptoSymbol,
                });
                return {
                    tokenName: tokenName,
                    cryptoSymbol: cryptoSymbol,
                    tokenAvatar: tokenAvatar,
                    cryptoAvatar: cryptoAvatar,
                    marketUrl: this.$routing.generate('token_show_trade', {
                        name: tokenName,
                        crypto: cryptoSymbol,
                    }),
                };
            }

            if (this.notificationTypes.new_buy_order === notification.type) {
                const jsonData = JSON.parse(notification.jsonData);

                return {
                    nickname: jsonData.nickname,
                    url: this.$routing.generate('token_show_trade', {
                        name: jsonData.tokenName,
                        crypto: this.rebrandingFunc(jsonData.crypto),
                    }),
                };
            }
        },
        isRewardType: function(type) {
            const rewardTypes = [
                this.notificationTypes.reward_participant,
                this.notificationTypes.reward_new,
                this.notificationTypes.reward_new_grouped,
                this.notificationTypes.bounty_new,
                this.notificationTypes.bounty_new_grouped,
                this.notificationTypes.reward_volunteer_new,
                this.notificationTypes.reward_volunteer_accepted,
                this.notificationTypes.reward_volunteer_completed,
                this.notificationTypes.reward_volunteer_rejected,
                this.notificationTypes.reward_participant_rejected,
                this.notificationTypes.reward_participant_delivered,
                this.notificationTypes.reward_participant_refunded,
            ];

            return rewardTypes.includes(type);
        },
        getGroupedNotificationMeta(notification) {
            return {
                number: notification.number ?? 1,
            };
        },
        kbTitle: function(kbLink) {
            return kbLink.split('-').join(' ');
        },
    },
};
</script>
