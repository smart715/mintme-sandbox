<template>
    <div class="card h-100 top-holders">
        <div class="card-header">
            Top Holders
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <template v-if="loaded">
                    <b-table v-if="hasTraders"
                        ref="table"
                        :items="traders"
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
import {FiltersMixin, LoggerMixin, NotificationMixin} from '../../mixins';
import HolderName from './HolderName';

export default {
    name: 'TopHolders',
    mixins: [FiltersMixin, LoggerMixin, NotificationMixin],
    components: {
        HolderName,
    },
    props: {
      tokenName: String,
    },
    data() {
        return {
            traders: null,
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
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        getTraders: function() {
            this.$axios.single.get(this.$routing.generate('top_holders', {
                name: this.tokenName,
            }))
            .then(({data}) => this.traders = data.map((row) => {
                return {
                    trader: row.user.profile.nickname,
                    traderAvatar: row.user.profile.image.avatar_small,
                    url: this.$routing.generate('profile-view', {nickname: row.user.profile.nickname}),
                    date: row.timestamp ? moment.unix(row.timestamp).format(GENERAL.dateFormat) : '-',
                    amount: Math.round(row.balance),
                };
            })).catch((err) => {
                this.sendLogs('error', 'Can not get top holders', err)
                .then(() => {}, () => {});
            });
        },
    },
    mounted: function() {
        this.getTraders();
        setInterval(() => this.getTraders(), 20 * 1000);
    },
};
</script>
