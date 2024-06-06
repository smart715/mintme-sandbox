<template>
    <div class="card card-fixed-medium token-list-fixed">
        <div class="card-header text-uppercase pl-3">
            <div
                v-if="profileOwner"
                v-html="$t('page.profile.tokens.i_own.header')"
            ></div>
            <template v-else>
                <div
                    class="d-inline-block"
                    v-b-tooltip.top="profileOwnerTooltipConfig"
                    v-html="$t('page.profile.tokens.user_owns.header', translationsContext)"
                ></div>
            </template>
        </div>
        <div class="card-body p-0">
            <template v-if="tokens.length">
                <div class="table-responsive table-restricted">
                    <b-table
                        :items="tokens"
                        :fields="fields"
                        tbody-tr-class="c-pointer"
                        @row-clicked="rowClickHandler"
                    >
                        <template v-slot:cell(name)="row">
                            <avatar
                                :image="row.item.tokenAvatar"
                                type="token"
                                size="small"
                                class="d-inline"
                                :key="row.item.tokenAvatar"
                            />
                            <span
                                v-b-tooltip="tokenNameTooltipConfig(row.item.name)"
                            >
                                {{  row.item.name | truncate(maxTokenNameLength) }}
                            </span>
                        </template>
                        <template v-slot:cell(amount)="row">
                            <img
                                v-if="row.item.rankImg"
                                :src="row.item.rankImg"
                                class="medal mr-1"
                                alt="medal"
                            />
                            <span>{{ row.item.amount | toMoney(TOK.subunit) | formatMoney }}</span>
                        </template>
                    </b-table>
                </div>
                <div class="text-center">
                    <m-button
                        v-if="showSeeMoreButton"
                        type="secondary-rounded"
                        @click="updateTableData"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                </div>
            </template>
            <div v-else class="card-body h-100 d-flex align-items-center justify-content-center">
                <span class="text-center py-4">
                    {{ $t('tokens.user.owns.no_yet') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {BTable, VBTooltip} from 'bootstrap-vue';
import {formatMoney, getRankMedalSrcByRank} from '../../utils';
import Avatar from '../Avatar';
import {MButton} from '../UI';
import {WEB, TOK} from '../../utils/constants';
import {
    FiltersMixin,
    MoneyFilterMixin,
    NotificationMixin,
} from '../../mixins/';

library.add(faCircleNotch);

export default {
    name: 'TokensUserOwns',
    props: {
        nickname: String,
        cryptos: Object,
        profileOwner: Boolean,
        tokensCount: Number,
        tokensUserOwnsProp: {
            type: [Object, Array],
            default: () => [],
        },
    },
    components: {
        BTable,
        MButton,
        Avatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
        MoneyFilterMixin,
        NotificationMixin,
    ],
    data() {
        return {
            maxLengthToTruncate: 15,
            maxTokenNameLength: 25,
            WEB: WEB,
            TOK: TOK,
            perPage: 10,
            currentPage: 1,
            tableData: null,
            tokensUserOwns: [],
            scrollListenerAutoStart: false,
            fields: [
                {
                    key: 'name',
                    label: this.$t('tokens.user.owns_name'),
                },
                {
                    key: 'amount',
                    label: this.$t('tokens.user.owns_amount'),
                    formatter: formatMoney,
                },
            ],
        };
    },
    computed: {
        translationsContext: function() {
            return {
                nickname: this.truncateFunc(this.nickname, this.maxLengthToTruncate),
            };
        },
        tokens: function() {
            if (null === this.tableData) {
                return [];
            }

            return Object.values(this.tableData).map((token) => {
                return {
                    name: token.name,
                    url: this.$routing.generate('token_show_intro', {name: token.name}),
                    tokenAvatar: token.avatar ? token.avatar.avatar_small : null,
                    cryptoSymbol: token.cryptoSymbol,
                    amount: token.available,
                    subunit: token.subunit,
                    showDeployedIcon: token.showDeployedIcon,
                    tokenizedImage: this.cryptos[token.cryptoSymbol].image.avatar_small,
                    rankImg: token.rank
                        ? getRankMedalSrcByRank(token.rank)
                        : null,
                };
            });
        },
        profileOwnerTooltipConfig: function() {
            return this.tooltipConfig(this.nickname);
        },
        showSeeMoreButton: function() {
            return this.tokensCount > this.currentPage * this.perPage;
        },
    },
    mounted() {
        this.tokensUserOwns = 'object' === typeof this.tokensUserOwnsProp
            ? Object.values(this.tokensUserOwnsProp)
            : this.tokensUserOwnsProp;

        this.tableData = this.tokensUserOwns.slice(0, this.perPage);
    },
    methods: {
        updateTableData: function() {
            ++this.currentPage;
            this.tableData = this.tokensUserOwns.slice(0, this.currentPage * this.perPage);
        },
        smallTitle: function(title) {
            return this.maxLengthToTruncate > title.length;
        },
        tooltipConfig: function(title) {
            return {
                title: title,
                boundary: 'viewport',
                variant: 'light',
                disabled: this.smallTitle(title),
            };
        },
        tokenNameTooltipConfig: function(tokenName) {
            return this.tooltipConfig(tokenName);
        },
        rowClickHandler: function(record) {
            window.location.href = record.url;
        },
    },
};
</script>
