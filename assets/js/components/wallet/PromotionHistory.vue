<template>
    <div class="px-0 pt-2 active-orders-tab mt-4">
        <table-header
            :header="$t('page.wallet.promotion_history.header')"
        />
        <template v-if="loaded">
            <div
                v-if="hasPromotionHistory"
                class="table-responsive fixed-head-table aligned-table layout-auto-table text-nowrap px-3 py-4"
                ref="table"
            >
                <b-table
                    thead-class="trading-head"
                    :items="history"
                    :fields="fieldsArray"
                    :sort-compare="$sortCompare(fields)"
                    :sort-by="fields.date.key"
                    :sort-desc="true"
                    sort-direction="desc"
                    sort-icon-left
                    no-sort-reset
                >
                    <template v-slot:[`head(${fields.amount.key})`]="data">
                        <span class="sorting-arrows"></span>
                        {{ data.label }}
                    </template>
                    <template v-slot:cell(tokenName)="row">
                        <div>
                            <a :href="row.item.tokenUrl" class="text-white">
                                <coin-avatar
                                    :is-user-token="true"
                                    :image="row.item.tokenAvatar"
                                />
                                <span>
                                    {{ row.value }}
                                </span>
                            </a>
                        </div>
                    </template>
                    <template v-slot:cell(userName)="row">
                        <div>
                            <a :href="row.item.profileUrl" class="text-white d-flex">
                                <avatar
                                    type="profile"
                                    size="small"
                                    :image="row.item.avatarUrl"
                                />
                                <span class="ml-2 text-truncate">
                                    {{ row.value }}
                                </span>
                            </a>
                        </div>
                    </template>
                    <template v-slot:cell(date)="row">
                        <div v-b-tooltip="{title: row.value.hoverFormatDate}">
                            {{ row.value.tableFormatDate }}
                        </div>
                    </template>
                </b-table>
            </div>
            <div class="table-bottom d-flex justify-content-around mt-2">
                <div v-if="loading" class="p-1 text-center">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
                <m-button
                    v-if="showSeeMoreButton"
                    type="secondary-rounded"
                    @click="updateTableData"
                >
                    {{ $t('see_more') }}
                </m-button>
            </div>
            <div v-if="!hasPromotionHistory">
                <p class="text-center p-5">{{ $t('wallet.history.no_transactions') }}</p>
            </div>
        </template>
        <template v-else>
            <div class="p-5 text-center">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </template>
    </div>
</template>

<script>

import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {BTable, VBTooltip} from 'bootstrap-vue';
import moment from 'moment';
import {MButton} from '../UI';
import {toMoney} from '../../utils';
import Avatar from '../Avatar';
import TableHeader from './TableHeader';
import {
    GENERAL,
    AIRDROP,
    BOUNTY,
    TOKEN_SHOP,
    SHARE_POST,
    TOKEN_SIGNUP,
    WALLET_ITEMS_BATCH_SIZE,
    REWARD_COMPLETED,
    REWARD_DELIVERED,
    REWARD_NOT_COMPLETED,
    REWARD_REFUNDED,
    TYPE_BOUNTY,
    TOKEN_DEPLOYMENT,
    TOKEN_CONNECTION,
    TOKEN_PROMOTION,
    COMMENT_TIP,
    transactionStatus,
    TOKEN_RELEASE_ADDRESS,
    TOKEN_NEW_MARKET,
    TIP_FEE_TYPE,
} from '../../utils/constants';
import {
    LazyScrollTableMixin,
    NotificationMixin,
    FiltersMixin,
    RebrandingFilterMixin,
    BnbToBscFilterMixin,
} from '../../mixins';
import CoinAvatar from '../CoinAvatar';

library.add(faCircleNotch);

export default {
    name: 'PromotionHistory',
    components: {
        BTable,
        MButton,
        Avatar,
        TableHeader,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        BnbToBscFilterMixin,
        NotificationMixin,
        FiltersMixin,
    ],
    data() {
        return {
            loading: false,
            tableData: null,
            currentPage: 0,
            perPage: WALLET_ITEMS_BATCH_SIZE,
            allHistoryLoaded: false,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.promotion_history.table.date'),
                    sortable: true,
                    formatter: (date) => {
                        return {
                            tableFormatDate: this.getDateFromTimestamp(date),
                            hoverFormatDate: moment.unix(date).format(GENERAL.dateTimeFormat),
                        };
                    },
                    thStyle: {width: '10rem'},
                },
                type: {
                    key: 'type',
                    label: this.$t('wallet.promotion_history.table.type'),
                    sortable: true,
                    type: 'string',
                    thStyle: {width: '10rem'},
                },
                status: {
                    key: 'status',
                    label: this.$t('wallet.promotion_history.table.status'),
                    sortable: true,
                    type: 'string',
                    tdClass: 'text-capitalize',
                    thStyle: {width: '10rem'},
                },
                tokenName: {
                    key: 'tokenName',
                    label: this.$t('wallet.promotion_history.table.token_name'),
                    sortable: true,
                    type: 'string',
                    thStyle: {width: '10rem'},
                },
                amount: {
                    key: 'amount',
                    label: this.$t('wallet.promotion_history.table.amount'),
                    sortable: true,
                    type: 'numeric',
                    tdClass: 'text-right',
                    thClass: 'text-right sorting-arrows-th',
                    thStyle: {width: '10rem'},
                },
                transaction: {
                    key: 'transaction',
                    label: this.$t('wallet.promotion_history.table.transaction'),
                    sortable: true,
                    type: 'string',
                    thStyle: {'min-width': '10rem'},
                },
                userName: {
                    key: 'userName',
                    label: this.$t('wallet.promotion_history.table.nickname'),
                    sortable: true,
                    type: 'string',
                    thStyle: {width: '10rem'},
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.tableData
                ? this.tableData.length
                : 0;
        },
        hasPromotionHistory: function() {
            return 0 < this.totalRows;
        },
        loaded: function() {
            return null !== this.tableData;
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        history: function() {
            return this.tableData.map((transaction) => {
                const user = COMMENT_TIP === transaction.type && transaction.commentAuthor
                    ? transaction.commentAuthor
                    : transaction.user;

                return {
                    date: transaction.createdAt,
                    type: this.getIncomingType(transaction),
                    tokenName: this.truncateFunc(transaction.token.name, 11),
                    status: this.getHumanizedStatus(transaction),
                    tokenAvatar: transaction.token.image,
                    amount: this.getHumanizedAmount(transaction),
                    transaction: this.getTransactionTypeTrans(transaction),
                    userName: user.profile.nickname,
                    tokenUrl: this.generateTokenUrl(transaction.token.name),
                    profileUrl: this.generateProfileUrl(user.profile.nickname),
                    avatarUrl: user.profile.image.avatar_small,
                };
            });
        },
        nextPage: function() {
            return this.currentPage + 1;
        },
        showSeeMoreButton: function() {
            return this.hasPromotionHistory
                && !this.allHistoryLoaded
                && !this.loading;
        },
    },
    mounted: function() {
        this.updateTableData();
    },
    methods: {
        updateTableData: async function() {
            this.loading = true;

            try {
                const response = await this.$axios.retry.get(
                    this.$routing.generate(
                        'promotion_history',
                        {
                            page: this.nextPage,
                        }
                    )
                );

                this.tableData = null === this.tableData
                    ? response.data
                    : this.tableData.concat(response.data);

                if (0 < response.data.length) {
                    this.currentPage++;
                }

                if (response.data.length < this.perPage) {
                    this.allHistoryLoaded = true;
                }
            } catch (err) {
                this.$logger.error('Service unavailable. Can not update promotion history', err);
                this.notifyError(this.$t('wallet.promotion_history.cant_load'));
            } finally {
                this.loading = false;
            }
        },
        generateTokenUrl: function(tokenName) {
            return this.$routing.generate('token_show_intro', {name: tokenName});
        },
        generateProfileUrl: function(userName) {
            return this.$routing.generate('profile-view', {nickname: userName});
        },
        getTransactionTypeTrans: function(transaction) {
            switch (transaction.type) {
                case AIRDROP: return this.$t('wallet.promotion_history.table.airdrop');
                case BOUNTY: return this.$t('wallet.promotion_history.table.bounty');
                case TOKEN_SHOP: return this.$t('wallet.promotion_history.table.token_shop');
                case SHARE_POST: return this.$t('wallet.promotion_history.table.share_post');
                case COMMENT_TIP: return TIP_FEE_TYPE === transaction.tipType
                    ? this.$t('wallet.promotion_history.table.comment_tip.fee_type')
                    : this.$t('wallet.promotion_history.table.comment_tip');
                case TOKEN_SIGNUP: return this.$t('wallet.promotion_history.table.token_signup');
                case TOKEN_RELEASE_ADDRESS: return this.$t('wallet.promotion_history.table.token_release_address');
                case TOKEN_NEW_MARKET: return this.$t('wallet.promotion_history.table.token_new_market', {
                    'crypto': this.rebrandingFunc(transaction.crypto.symbol),
                });
                case TOKEN_DEPLOYMENT: return this.$t('wallet.promotion_history.table.token_deployment', {
                    'blockchain': this.getShortBlockchain(transaction.crypto.symbol),
                });
                case TOKEN_CONNECTION: return this.$t('wallet.promotion_history.table.token_connection', {
                    'blockchain': this.getShortBlockchain(transaction.crypto.symbol),
                });
                case TOKEN_PROMOTION: return this.$t('wallet.promotion_history.table.token_promotion');
            }
        },
        getShortBlockchain: function(symbol) {
            return this.rebrandingFunc(this.bnbToBscFunc(symbol));
        },
        getIncomingType: function(transaction) {
            const isTokenOwner = this.$store.getters['user/getId'] === transaction.token.ownerId;
            const isTokenShop = TOKEN_SHOP === transaction.type;
            const isOutgoing = isTokenShop
                ? !isTokenOwner
                : isTokenOwner;

            return isOutgoing
                ? this.$t('wallet.promotion_history.table.outgoing')
                : this.$t('wallet.promotion_history.table.incoming');
        },
        getDateFromTimestamp: function(timeStamp) {
            return 0 >= timeStamp
                ? '-'
                : moment.unix(timeStamp).format(GENERAL.dateTimeFormatTable);
        },
        getHumanizedStatus(transaction) {
            switch (transaction.status) {
                case REWARD_NOT_COMPLETED:
                    return this.$t('bounty.status.not_completed');
                case REWARD_REFUNDED:
                    return this.$t('bounty.status.refunded');
                case REWARD_COMPLETED:
                    return TYPE_BOUNTY === transaction.type
                        ? this.$t('bounty.status.completed')
                        : this.$t('reward.status.in_delivery');
                case REWARD_DELIVERED:
                    return this.$t('reward.status.delivered');
                case transactionStatus.PENDING:
                    return this.$t('wallet.status.pending');
                case transactionStatus.PAID:
                    return this.$t('wallet.status.paid');
                case transactionStatus.ERROR:
                    return this.$t('wallet.status.error');
                default:
                    return this.$t('bounty.status.completed');
            }
        },
        getHumanizedAmount(transaction) {
            const toHumanizeTypes = [
                TOKEN_CONNECTION,
                TOKEN_DEPLOYMENT,
                TOKEN_RELEASE_ADDRESS,
                TOKEN_NEW_MARKET,
                COMMENT_TIP,
                TOKEN_PROMOTION,
            ];

            const type = transaction.type;

            if (!toHumanizeTypes.includes(type)) {
                return toMoney(transaction.amount);
            }

            let paymentSymbol;

            if (COMMENT_TIP === type) {
                paymentSymbol = TIP_FEE_TYPE === transaction.tipType
                    ? transaction.currency
                    : '';
            } else if (TOKEN_PROMOTION === type) {
                paymentSymbol = transaction.currency;
            } else {
                paymentSymbol = TOKEN_NEW_MARKET === type
                    ? transaction.cryptoCost.moneySymbol
                    : transaction.crypto.moneySymbol;
            }

            return `${toMoney(transaction.amount)} ${this.rebrandingFunc(paymentSymbol)}`;
        },
    },
};
</script>
