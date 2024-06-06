<template>
    <div v-if="!shouldFold" class="card p-2">
        <div class="d-flex justify-content-between">
            <span
                class="font-size-3 font-weight-semibold header-highlighting"
                v-html="$t('trade.top_holders.header')"
            >
            </span>
            <guide class="tooltip-center font-size-tooltip">
                <template slot="body">
                    <span v-html="$t('trade.top_holders.tooltip.header', translationsContext)" />
                </template>
            </guide>
        </div>
        <div v-if="serviceUnavailable" class="card-body h-100 d-flex align-items-center justify-content-center">
            <span class="text-center py-4">
                {{ this.$t('toasted.error.service_unavailable_short') }}
            </span>
        </div>
        <div v-else-if="hasTraders" class="card-body p-0 pt-3">
            <div class="table-responsive">
                <b-table
                    ref="table"
                    :items="holdersToShow"
                    :fields="fields"
                >
                    <template v-slot:cell(trader)="row">
                        <elastic-text
                            :value="row.value"
                            :img="row.item.traderAvatar"
                            :frame="row.item.wreath"
                            :url="row.item.url"
                        />
                    </template>
                </b-table>
            </div>
            <div class="d-flex justify-content-center my-2">
                <m-button
                    v-if="holders.length > 5"
                    type="secondary-rounded"
                    @click="isListOpened=!isListOpened"
                >
                    {{ isListOpened
                           ? $t('token.row_tables.show_less')
                           : $t('token.row_tables.show_more')
                    }}
                </m-button>
            </div>
        </div>
        <div v-else class="card-body h-100 d-flex align-items-center justify-content-center">
            <span class="text-center py-4">
                {{ $t('trade.top_holders.no_holders') }}
            </span>
        </div>
    </div>
    <div v-else class="card">
        <div class="card-body">
            <div class="text-center">
                <b-link @click="isFolded = false" v-html="$t('show', {thing: $t('trade.top_holders.header')})"></b-link>
            </div>
        </div>
    </div>
</template>

<script>
import {BTable, BLink} from 'bootstrap-vue';
import {formatMoney, generateCoinAvatarHtml, getRankWreathSrcByRank} from '../../utils';
import {FiltersMixin, NotificationMixin, WebSocketMixin} from '../../mixins';
import ElasticText from '../ElasticText';
import {MButton} from '../UI';
import Guide from '../Guide';
import {RANK_WREATHS} from '../../utils/constants';

export default {
    name: 'TopHolders',
    mixins: [FiltersMixin, NotificationMixin, WebSocketMixin],
    components: {
        Guide,
        BTable,
        BLink,
        ElasticText,
        MButton,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        tradersProp: {
            type: Array,
            default: () => null,
        },
        isMobileScreen: Boolean,
        serviceUnavailable: Boolean,
    },
    data() {
        return {
            traders: this.tradersProp ?? [],
            isListOpened: false,
            fields: [
                {
                    key: 'trader',
                    label: this.$t('trade.top_holders.trader'),
                    thClass: 'pl-3',
                    tdClass: 'pl-3',
                },
                {
                    key: 'amount',
                    label: this.$t('trade.top_holders.amount'),
                    formatter: formatMoney,
                    thClass: 'pr-3 text-right',
                    tdClass: 'pr-3 text-right',
                },
            ],
            isFolded: true,
        };
    },
    computed: {
        hasTraders: function() {
            return 0 < this.traders.length;
        },
        holders: function() {
            return this.traders.map((row) => {
                const wreath = RANK_WREATHS[row.rank];

                return {
                    trader: row.user.profile.nickname,
                    traderAvatar: row.user.profile.image.avatar_small,
                    url: this.$routing.generate('profile-view', {nickname: row.user.profile.nickname}),
                    amount: Math.round(row.balance),
                    wreath: wreath ? getRankWreathSrcByRank(row.rank) : null,
                };
            });
        },
        holdersToShow: function() {
            return this.isListOpened || 5 > this.holders.length ? this.holders : this.holders.slice(0, 5);
        },
        shouldFold: function() {
            return this.isMobileScreen && this.isFolded;
        },
        translationsContext: function() {
            return {
                tokenName: this.tokenName,
                tokenAvatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
            };
        },
    },
    methods: {
        scrollDown: function() {
            const parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        getTraders: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('top_holders', {
                    name: this.tokenName,
                }))
                    .then(({data}) => {
                        this.traders = data;
                        resolve(data);
                    })
                    .catch((err) => {
                        reject(this.$logger.error('Can not get top holders', err));
                    });
            });
        },
    },
    mounted: function() {
        this.addMessageHandler((response) => {
            if ('deals.update' === response.method) {
                this.getTraders();
            }
        }, 'update-top-holders', 'TopHolders');
    },
};
</script>
