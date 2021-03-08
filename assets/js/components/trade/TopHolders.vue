<template>
    <div class="card h-100 top-holders">
        <div class="card-header">
            {{ $t('trade.top_holders.header') }}
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <template v-if="loaded">
                    <b-table v-if="hasTraders"
                        ref="table"
                        :items="holders"
                        :fields="fields"
                    >
                        <template v-slot:cell(trader)="row">
                            <holder-name :value="row.value" :img="row.item.traderAvatar" :url="row.item.url"/>
                        </template>
                    </b-table>
                    <div v-else>
                        <p class="text-center p-5">
                            {{ $t('trade.top_holders.no_holders') }}
                        </p>
                    </div>
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';
import {formatMoney} from '../../utils';
import {GENERAL} from '../../utils/constants';
import {FiltersMixin, LoggerMixin, NotificationMixin, WebSocketMixin} from '../../mixins';
import HolderName from './HolderName';

export default {
    name: 'TopHolders',
    mixins: [FiltersMixin, LoggerMixin, NotificationMixin, WebSocketMixin],
    components: {
        HolderName,
    },
    props: {
        tokenName: String,
        tradersProp: {
            type: Array,
            default: () => null,
        },
    },
    data() {
        return {
            traders: this.tradersProp,
            fields: [
                {
                    key: 'trader',
                    label: this.$t('trade.top_holders.trader'),
                },
                {
                    key: 'date',
                    label: this.$t('trade.top_holders.date'),
                },
                {
                    key: 'amount',
                    label: this.$t('trade.top_holders.amount'),
                    formatter: formatMoney,
                },
            ],
        };
    },
    computed: {
        loaded: function() {
            return null !== this.traders;
        },
        hasTraders: function() {
            return this.traders.length > 0;
        },
        holders: function() {
            return this.traders.map((row) => {
                return {
                    trader: row.user.profile.nickname,
                    traderAvatar: row.user.profile.image.avatar_small,
                    url: this.$routing.generate('profile-view', {nickname: row.user.profile.nickname}),
                    date: row.timestamp ? moment.unix(row.timestamp).format(GENERAL.dateFormat) : '-',
                    amount: Math.round(row.balance),
                };
            });
        },
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
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
                    .catch((err) => reject(
                        this.sendLogs('error', 'Can not get top holders', err)
                    ));
            });
        },
    },
    mounted: function() {
        if (!this.traders) {
            this.getTraders();
        }

        this.getTraders().then(() => {
            this.addMessageHandler((response) => {
                if ('deals.update' === response.method) {
                    this.getTraders();
                }
            }, 'update-top-holders', 'TopHolders');
        });
    },
};
</script>
