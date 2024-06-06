<template>
    <div class="px-0 py-2 wallet-tab">
        <buy-crypto
            class="px-4 mt-1"
            :trading-url="tradingUrl"
            :coinify-ui-url="coinifyUiUrl"
            :coinify-partner-id="coinifyPartnerId"
            :coinify-crypto-currencies="coinifyCryptoCurrencies"
            :predefined-tokens="predefinedItems"
            :mintme-exchange-mail-sent="mintmeExchangeMailSent"
            :view-only="viewOnly"
        />
        <div class="mt-4">
            <table-header
                :header="$t('page.wallet.coins.header')"
            >
                <template v-slot:right-side>
                    <div class="total-balance ml-auto">
                        <font-awesome-icon
                            class="coins-icon text-primary"
                            :icon="{prefix: 'fas', iconName: 'coins'}"
                        />
                        <span class="btc">
                            {{ coinsTotalBalanceBTC | toMoney(subunits.BTC) | formatMoney }} BTC
                        </span>
                        <span class="usd">
                            ${{ coinsTotalBalanceUSD | toMoney(subunits.USD) | formatMoney }} USD
                        </span>
                    </div>
                </template>
            </table-header>
            <div v-if="showLoadingIconP" class="p-5 text-center">
                <span v-if="serviceUnavailable">{{ serviceUnavailableMsg }}</span>
                <div v-else class="spinner-border spinner-border-sm" role="status"></div>
            </div>
            <div v-else class="table-responsive mt-2 py-4 px-3">
                <b-table hover :items="predefinedItems" :fields="predefinedTokenFields">
                    <template v-slot:cell(name)="data">
                        <div
                            class="d-flex align-items-center"
                            :class="getCryptoClass(data.item.cryptoSymbol)"
                        >
                            <coin-avatar
                                :class="getCryptoAvatarClass(data.item.cryptoSymbol)"
                                :symbol="data.item.cryptoSymbol"
                                is-crypto
                            />
                            <a :href="data.item.url" class="text-white">
                                {{ data.item.fullname|rebranding }} ({{ data.item.name|rebranding }})
                            </a>
                        </div>
                    </template>
                    <template v-slot:cell(available)="data">
                        <span class="text-break">
                            {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                        </span>
                    </template>
                    <template v-slot:head(bonus)>
                        <div class="bb-column-label">
                            {{ $t('wallet.bonus_balance') }}
                            <guide>
                                <template slot="body">
                                    {{ $t('wallet.bonus_balance_guide_crypto') }}
                                </template>
                            </guide>
                        </div>
                    </template>
                    <template v-slot:cell(bonus)="data">
                        <span class="text-break">
                            {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                        </span>
                    </template>
                    <template v-slot:cell(action)="data">
                        <div class="row pl-2">
                            <div v-b-tooltip="tooltipDepositOrWithdrawButton(data, transactionType.DEPOSIT)">
                                <button
                                    class="btn btn-transparent d-flex flex-row pl-2 bg-transparent"
                                    :class="actionButtonClass(isCryptoDepositDisabled(data))"
                                    :disabled="isCryptoDepositDisabled(data)"
                                    @click="openDeposit(data.item.name)"
                                >
                                    <div class="hover-icon">
                                        <font-awesome-icon
                                            class="icon-default"
                                            :class="{
                                                'text-muted': isCryptoDepositDisabled(data)
                                            }"
                                            :icon="['fac', 'deposit']"
                                        />
                                        <span
                                            class="pl-2 text-xs align-middle"
                                            :class="{'wallet-action-txt': data.item.deployed}"
                                        >
                                            {{ $t('wallet.deposit') }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                            <div v-b-tooltip="tooltipDepositOrWithdrawButton(data, transactionType.WITHDRAW)">
                                <button
                                    class="btn btn-transparent d-flex flex-row pl-2 bg-transparent"
                                    :class="actionButtonClass(isCryptoWithdrawalDisabled(data))"
                                    :disabled="isCryptoWithdrawalDisabled(data)"
                                    @click="openWithdraw(data.item.name)"
                                >
                                    <div class="hover-icon">
                                        <font-awesome-icon
                                            class="icon-default"
                                            :class="{
                                                'text-muted': isCryptoWithdrawalDisabled(data)
                                            }"
                                            :icon="['fac', 'withdraw']"
                                        />
                                        <span
                                            class="pl-2 text-xs align-middle"
                                            :class="{'wallet-action-txt': data.item.deployed}"
                                        >
                                            {{ $t('wallet.withdraw') }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </template>
                </b-table>
            </div>
        </div>
        <div class="mt-4">
            <table-header
                :header="$t('page.wallet.tokens.header')"
            >
                <template v-slot:right-side>
                    <div class="custom-control custom-checkbox ml-auto pb-1">
                        <input
                            v-model="isHiddenZeroBalances"
                            :checked="isHiddenZeroBalances"
                            type="checkbox"
                            id="hideZeroBalances"
                            class="custom-control-input"
                            @change="hiddenZeroBalancesChanged"
                        >
                        <label class="custom-control-label" for="hideZeroBalances">
                            {{ $t('wallet.hide_zero_balances') }}
                        </label>
                    </div>
                </template>
            </table-header>
            <div v-if="showLoadingIcon" class="text-center p-5">
                <span v-if="serviceUnavailable">{{ serviceUnavailableMsg }}</span>
                <div v-else class="spinner-border spinner-border-sm" role="status"></div>
            </div>
            <div v-else class="mt-2 table-responsive py-4 px-3">
                <div v-if="hasTokens">
                    <b-table hover :items="validTokens" :fields="tokenFields">
                        <template v-slot:cell(name)="data">
                            <div
                                v-b-tooltip="getTokenNameTooltip(data.item.name)"
                                class="d-flex align-items-center"
                            >
                                <coin-avatar
                                    class="mr-2"
                                    :image="data.item.image"
                                    :symbol="data.item.cryptoSymbol"
                                    :is-deployed="data.item.deployed"
                                    is-user-token
                                />
                                <span v-if="data.item.blocked">
                                    <span class="text-muted">
                                        {{ data.item.name | truncate(14) }}
                                    </span>
                                </span>
                                <span v-else>
                                    <a :href="generatePairUrl(data.item)" class="text-white">
                                        {{ data.item.name | truncate(14) }}
                                    </a>
                                </span>
                                <coin-avatar
                                    v-if="data.item.deployed"
                                    class="ml-2"
                                    :image="data.item.image"
                                    :symbol="data.item.cryptoSymbol"
                                    :is-deployed="data.item.deployed"
                                    :is-crypto="true"
                                />
                            </div>
                        </template>
                        <template v-slot:cell(available)="data">
                            <span class="text-break">
                                {{ data.value | toMoney(data.item.subunit) | formatMoney }}
                            </span>
                        </template>
                        <template v-slot:head(bonus)>
                            <div class="bb-column-label">
                                {{ $t('wallet.bonus_balance') }}
                                <guide>
                                    <template slot="body">
                                        {{ $t('wallet.bonus_balance_guide') }}
                                    </template>
                                </guide>
                            </div>
                        </template>
                        <template v-slot:cell(bonus)="data">
                            <span class="text-break">
                                {{ (data.value || '0') | toMoney(data.item.subunit) | formatMoney }}
                            </span>
                        </template>
                        <template v-slot:cell(action)="data">
                            <div class="row pl-2">
                                <div v-b-tooltip="tooltipDepositOrWithdrawButton(data, transactionType.DEPOSIT)">
                                    <button
                                        class="btn btn-transparent d-flex pl-2 bg-transparent"
                                        :disabled="isTokenDepositDisabled(data)"
                                        :class="actionButtonClass(isTokenDepositDisabled(data))"
                                        @click="openDeposit(data.item.name)"
                                    >
                                        <div class="hover-icon">
                                            <font-awesome-icon
                                                class="icon-default"
                                                :icon="['fac', 'deposit']"
                                            />
                                            <span class="pl-2 text-xs align-middle wallet-action-txt">
                                                {{ $t('wallet.deposit') }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div v-b-tooltip="tooltipDepositOrWithdrawButton(data, transactionType.WITHDRAW)">
                                    <button
                                        class="btn btn-transparent d-flex pl-2 bg-transparent"
                                        :class="actionButtonClass(isTokenWithdrawalDisabled(data))"
                                        :disabled="isTokenWithdrawalDisabled(data)"
                                        @click="openWithdraw(data.item.name)"
                                    >
                                        <div class="hover-icon">
                                            <font-awesome-icon
                                                class="icon-default"
                                                :icon="['fac', 'withdraw']"
                                            />
                                            <span class="pl-2 text-xs align-middle wallet-action-txt">
                                                {{ $t('wallet.withdraw') }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                                <div v-b-tooltip="tooltipRemoveTokenButton(data)">
                                    <button
                                        class="btn btn-transparent d-flex px-0 mt-1"
                                        :class="actionButtonClass(!canRemoveToken(data))"
                                        :disabled="!canRemoveToken(data)"
                                        @click="openTokenDeleteModal(data)"
                                    >
                                        <font-awesome-icon
                                            icon="times"
                                            :class="canRemoveToken(data) ? 'text-danger' : 'text-muted'"
                                        />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </b-table>
                </div>
                <div v-else>
                    <table v-if="!showLoadingIcon && validTokens.length <= 0" class="table no-owned-token">
                        <tbody>
                            <tr>
                                <td class="first-field">
                                    <div class="text-nowrap">
                                        {{ $t('wallet.create_token_1') }}
                                        <a :href="createTokenUrl" class="link highlight">
                                            {{ $t('wallet.create_token_2') }}
                                        </a>
                                    </div>
                                </td>
                                <td class="field-table">&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    {{ $t('wallet.no_tokens_1') }}
                                    <a :href="tradingUrl" class="link highlight"> {{ $t('wallet.no_tokens_2') }} </a>
                                    {{ $t('wallet.no_tokens_3') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <m-button
                        v-if="showSeeMoreButton"
                        type="secondary-rounded"
                        @click="fetchTokens"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                </div>
            </div>
        </div>
        <withdraw-modal
            :panel-env="panelEnv"
            :visible="showModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :available-balances="availableBalances"
            :withdraw-url="withdrawUrl"
            :twofa="twofa"
            :subunit="currentSubunit"
            :no-close="true"
            :expiration-time="expirationTime"
            :token-avatar="tokenImageUrl"
            :currency-mode="currencyMode"
            :is-pausable="isPausable"
            :min-withdrawal="minWithdrawal"
            :withdraw-add-phone-modal-visible="withdrawAddPhoneModalVisible"
            @on-withdraw-phone-verified="onWithdrawPhoneVerified"
            @withdraw-close-add-phone-modal="withdrawAddPhoneModalVisible = false "
            @close="closeWithdraw"
        />
        <deposit-modal
            :visible="showDepositModal"
            :currency="selectedCurrency"
            :is-token="isTokenModal"
            :token="selectedToken"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            :no-close="false"
            :token-avatar="tokenImageUrl"
            :tokenHasTax="selectedTokenHasTax"
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(selectedCurrency)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
        <confirm-modal
            :visible="showTokenDeleteModal"
            @cancel="showTokenDeleteModal = false"
            @close="showTokenDeleteModal = false"
            @confirm="onTokenDeleteConfirm"
        >
            <slot>
                <p
                    v-html="deleteTokenMessage"
                    class="text-white modal-title text-break"
                ></p>
            </slot>
            <template v-slot:confirm>
                {{ $t('wallet.delete_token.button_confirm') }}
            </template>
            <template v-slot:cancel>
                {{ $t('no') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import WithdrawModal from '../modal/WithdrawModal';
import DepositModal from '../modal/DepositModal';
import TableHeader from './TableHeader';
import {
    WebSocketMixin,
    FiltersMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    DepositModalMixin,
} from '../../mixins';
import Decimal from 'decimal.js';
import {
    webSymbol,
    btcSymbol,
    BTC,
    WEB,
    HTTP_ACCESS_DENIED,
    transactionType,
    TOK,
} from '../../utils/constants';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {deposit as depositIcon, withdraw as withdrawIcon} from '../../utils/icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import BuyCrypto from './BuyCrypto';
import {BTable, VBTooltip} from 'bootstrap-vue';
import ConfirmModal from '../modal/ConfirmModal';
import {MButton} from '../UI';
import CoinAvatar from '../CoinAvatar';
import Guide from '../Guide';

library.add(depositIcon, withdrawIcon, faTimes);

export default {
    name: 'Wallet',
    mixins: [
        WebSocketMixin,
        FiltersMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        DepositModalMixin,
    ],
    components: {
        TableHeader,
        BTable,
        BuyCrypto,
        WithdrawModal,
        DepositModal,
        FontAwesomeIcon,
        ConfirmModal,
        CoinAvatar,
        Guide,
        MButton,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        panelEnv: String,
        withdrawUrl: {type: String, required: true},
        createTokenUrl: String,
        tradingUrl: String,
        depositMoreProp: String,
        twofa: String,
        expirationTime: Number,
        disabledCryptos: Array,
        isUserBlocked: Boolean,
        coinifyUiUrl: String,
        coinifyPartnerId: Number,
        coinifyCryptoCurrencies: Array,
        profileNickname: String,
        mintmeExchangeMailSent: Boolean,
        minAmount: Number,
        viewOnly: Boolean,
        mercureHubUrl: String,
        userTokensPerPage: Number,
        ownTokensCount: Number,
        minWithdrawal: Object,
    },
    data() {
        return {
            serviceUnavailable: false,
            depositMore: null,
            tokens: null,
            predefinedTokens: null,
            showModal: false,
            selectedCurrency: null,
            isTokenModal: false,
            isCreatedOnMintmeSite: false,
            isPausable: false,
            selectedToken: null,
            isOwner: false,
            noClose: false,
            addPhoneModalMessageType: 'deposit_withdrawal',
            selectedTokenHasTax: false,
            tooltipOptions: {
                placement: 'bottom',
                arrow: true,
                trigger: 'mouseenter',
                delay: [100, 200],
            },
            predefinedTokenFields: [
                {
                    key: 'name',
                    label: this.$t('wallet.table.name'),
                    class: 'first-field',
                },
                {
                    key: 'available',
                    label: this.$t('wallet.table.available'),
                    class: 'field-table',
                },
                {
                    key: 'bonus',
                    label: '',
                    class: 'field-table',
                },
                {
                    key: 'action',
                    label: this.$t('wallet.table.action'),
                    sortable: false,
                    class: 'action-field',
                },
            ],
            tokenFields: [
                {
                    key: 'name',
                    label: this.$t('wallet.table.name'),
                    class: 'first-field',
                },
                {
                    key: 'available',
                    label: this.$t('wallet.table.available'),
                    class: 'field-table',
                },
                {
                    key: 'bonus',
                    class: 'field-table',
                },
                {
                    key: 'action',
                    label: this.$t('wallet.table.action'),
                    sortable: false,
                    class: 'action-field',
                },
            ],
            tokensNetworks: {},
            cryptosNetworks: {},
            showTokenDeleteModal: false,
            dataToDelete: {},
            coinConvertionRates: null,
            tokenSymbolLength: 15,
            subunits: {
                BTC: BTC.subunit,
                USD: 2,
            },
            withdrawFnToCallAfterVerification: null,
            isHiddenZeroBalances: false,
            isWithdrawDelaysPassed: true,
            transactionType: {
                WITHDRAW: transactionType.WITHDRAW,
                DEPOSIT: transactionType.DEPOSIT,
            },
            tokenImageUrl: null,
            assetQueryMessageIds: new Set(),
            tableData: {},
            page: 1,
            allTokensLoaded: false,
            withdrawAddPhoneModalVisible: false,
            hasConfirmedAlert: false,
        };
    },
    computed: {
        serviceUnavailableMsg: function() {
            return this.$t('toasted.error.service_unavailable_support');
        },
        availableBalances: function() {
            const allAssets = this.predefinedItems.concat(this.items);

            return allAssets.reduce((balances, asset) => {
                balances[asset.name] = asset.available;

                return balances;
            }, {});
        },
        currentSubunit: function() {
            if (
                (this.isTokenModal && !this.tokens) ||
                (!this.isTokenModal && !this.predefinedTokens)
            ) {
                return WEB.subunit;
            }

            const asset = this.isTokenModal
                ? this.tokens[this.selectedCurrency]
                : this.predefinedTokens[this.selectedCurrency];

            return asset ? asset.subunit : WEB.subunit;
        },
        deleteTokenMessage: function() {
            return this.$t('wallet.delete_token.message');
        },
        currentTokenNetworks: function() {
            return this.tokensNetworks[this.selectedCurrency] || null;
        },
        currentCryptoNetworks: function() {
            return this.cryptosNetworks[this.selectedCurrency] || null;
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        hasTokens: function() {
            return 0 < Object.values(this.tokens || {})
                .filter((token) => !token.removed).length;
        },
        allTokens: function() {
            return Object.assign({}, this.tokens || {}, this.predefinedTokens || {});
        },
        allTokensName: function() {
            return Object.values(this.allTokens).map((token) => {
                return token.identifier;
            });
        },
        predefinedItems: function() {
            return this.tokensToArray(this.predefinedTokens || {}).map((item) => {
                item.url = this.rebrandingFunc(this.generateCoinUrl(item));
                return item;
            });
        },
        items: function() {
            return this.tokensToArray(this.tokens || {});
        },
        showLoadingIconP: function() {
            return (null === this.predefinedTokens);
        },
        showLoadingIcon: function() {
            return (null === this.tokens);
        },
        coinsTotalBalanceBTC: function() {
            if (null === this.coinConvertionRates || null === this.predefinedTokens) {
                return 0;
            }

            let coinsTotalBalanceBTC = new Decimal(0);

            Object.values(this.predefinedTokens).forEach((coin) => {
                const coinBalance = new Decimal(coin.available).times(this.coinConvertionRates[coin.cryptoSymbol].BTC);
                coinsTotalBalanceBTC = coinsTotalBalanceBTC.plus(coinBalance);
            });

            return coinsTotalBalanceBTC.toString();
        },
        coinsTotalBalanceUSD: function() {
            if (null === this.coinConvertionRates || null === this.predefinedTokens) {
                return 0;
            }

            return new Decimal(this.coinsTotalBalanceBTC).times(this.coinConvertionRates.BTC.USD).toString();
        },
        validTokens: function() {
            const userTokens = this.isHiddenZeroBalances
                ? this.items.filter((token) => 0 < token.available && !token.removed)
                : this.items.filter((token) => !token.removed);

            return userTokens.sort((a, b) => b.available - a.available);
        },
        totalRows: function() {
            return Object.keys(this.tableData || {}).length;
        },
        hasRows: function() {
            return 0 < this.totalRows;
        },
        showSeeMoreButton: function() {
            return this.hasRows && !this.allTokensLoaded;
        },
    },
    mounted: function() {
        if (null !== window.localStorage.getItem('mintme_signedup_from_quick_trade')) {
            this.depositMore = window.localStorage.getItem('mintme_quick_trade_currency');

            window.localStorage.removeItem('mintme_signedup_from_quick_trade');
            window.localStorage.removeItem('mintme_quick_trade_currency');
        }

        const es = new EventSource(
            `${this.mercureHubUrl}?topic=${encodeURIComponent('withdraw/' + this.userId)}`,
            {withCredentials: true}
        );

        es.onmessage = (event) => {
            const data = JSON.parse(event.data);
            const id = parseInt(Math.random().toString().substring(2));
            const asset = (this.tokens[data.tradable] && this.tokens[data.tradable].identifier)
                || (this.predefinedTokens[data.tradable] && this.predefinedTokens[data.tradable].identifier);

            if (!asset) {
                return;
            }

            this.sendMessage(JSON.stringify({
                method: 'asset.query',
                params: [asset],
                id,
            }));
            this.assetQueryMessageIds.add(id);
        };

        this.isHiddenZeroBalances = 'true' === window.localStorage.getItem('mintme_hide_zero_balances');

        Promise.all([
            this.fetchTokens()
                .then(() => {
                    this.authorize()
                        .then(() => {
                            this.addMessageHandler((response) => {
                                if ('asset.update' === response.method) {
                                    this.updateBalances(response.params[0]);
                                }

                                if (this.assetQueryMessageIds.has(response.id)) {
                                    this.assetQueryMessageIds.delete(response.id);
                                    this.updateBalances(response.result);
                                }
                            }, 'wallet-asset-update', 'Wallet');

                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: this.allTokensName,
                                id: parseInt(Math.random().toString().substring(2)),
                            }));
                        })
                        .catch((err) => {
                            this.notifyError(
                                this.$t('toasted.error.can_not_connect')
                            );
                            this.$logger.error('Can not connect to internal services', err);
                        });

                    this.sendMessage(JSON.stringify({
                        method: 'price.subscribe',
                        params: this.generateTokenMarketNames(),
                        id: parseInt(Math.random().toString().substring(2)),
                    }));
                })
                .catch((err) => {
                    this.$logger.error('Service unavailable. Can not update tokens now', err);
                }),
        ])
            .then(() => {
                if (null === this.depositMore) {
                    this.depositMore = this.depositMoreProp;
                }

                this.openDepositMore();
            })
            .catch((err) => {
                this.$logger.error('Service unavailable. Can not load wallet data now.', err);
            });

        this.$axios.retry.get(this.$routing.generate('exchange_rates'))
            .then((res) => {
                this.coinConvertionRates = res.data;
            })
            .catch((err) => {
                this.$logger.error('Service unavailable. Can not load convert data now', err);
            });
    },
    methods: {
        getCryptoClass: function(symbol) {
            return symbol === webSymbol ? 'ml-n2' : '';
        },
        getCryptoAvatarClass: function(symbol) {
            return symbol !== webSymbol ? 'mr-2' : '';
        },
        getCryptoNetworks: async function(symbol) {
            try {
                const response = await this.$axios.retry(this.$routing.generate('get_crypto_networks', {symbol}));
                this.$set(this.cryptosNetworks, symbol, this.parseNetworksData(response.data));
            } catch (error) {
                this.$logger.error('Error while get crypto networks', error);
            }
        },
        getTokenNetworks: async function(name) {
            try {
                const response = await this.$axios.retry(this.$routing.generate('get_token_networks', {name}));
                this.$set(this.tokensNetworks, name, this.parseNetworksData(response.data, name));
            } catch (error) {
                this.$logger.error('Error while get token networks', error);
            }
        },
        parseNetworksData: function(networks, name = null) {
            return networks.reduce((acc, network) => {
                const symbol = network.networkInfo.symbol;
                network.feeCurrency = TOK.symbol === network.feeCurrency && name
                    ? name
                    : network.feeCurrency;

                acc[symbol] = {symbol, ...network};

                return acc;
            }, {});
        },
        hiddenZeroBalancesChanged() {
            window.localStorage.setItem('mintme_hide_zero_balances', this.isHiddenZeroBalances.toString());
        },
        checkIfUserAbleToWithdraw(fnToCall, currency) {
            this.withdrawFnToCallAfterVerification = {
                name: fnToCall,
                currency: currency,
            };

            if (this.withdrawalPhoneRequired && !this.hasPhoneVerified) {
                this.withdrawAddPhoneModalVisible = true;
                return false;
            }

            return true;
        },
        isCryptoDepositDisabled: function(data) {
            const action = this.getCryptoActionDisabled(data.item.cryptoSymbol, 'DepositsDisabled');

            return this.isCryptoActionDisabled(data)
                || this.disabledServicesConfig[action]
                || this.disabledServicesConfig.coinDepositsDisabled
                || !!this.disabledServicesConfig.depositsDisabled[data.item.cryptoSymbol];
        },
        isCryptoWithdrawalDisabled: function(data) {
            const action = this.getCryptoActionDisabled(data.item.cryptoSymbol, 'WithdrawalsDisabled');

            return this.isCryptoActionDisabled(data)
                || this.disabledServicesConfig[action]
                || this.disabledServicesConfig.coinWithdrawalsDisabled
                || !!this.disabledServicesConfig.withdrawalsDisabled[data.item.cryptoSymbol];
        },
        isCryptoActionDisabled: function(data) {
            return this.areCryptoTokenActionsDisabled()
                || this.isUserBlocked
                || this.isDisabledCrypto(data.name);
        },
        getCryptoActionDisabled: function(crypto, action) {
            const symbol = this.rebrandingFunc(crypto).toLowerCase();

            return symbol.concat(action);
        },
        isTokenDepositDisabled: function(data) {
            return this.isTokenActionDisabled(data)
                || this.disabledServicesConfig.tokenDepositsDisabled || data.item.depositsDisabled;
        },
        isTokenWithdrawalDisabled: function(data) {
            return this.isTokenActionDisabled(data)
                || this.disabledServicesConfig.tokenWithdrawalsDisabled || data.item.withdrawalsDisabled;
        },
        isTokenActionDisabled: function(data) {
            return this.areCryptoTokenActionsDisabled()
                || data.item.blocked
                || !data.item.deployed
                || this.disabledServicesConfig.allServicesDisabled;
        },
        areCryptoTokenActionsDisabled: function() {
            return this.viewOnly;
        },
        actionButtonClass: function(disabled) {
            return disabled
                ? 'text-muted pointer-events-none'
                : 'text-white';
        },
        tooltipRemoveTokenButton: function(data) {
            if (data.item.owner) {
                return this.$t('wallet.disabled.delete_token_owner');
            }

            return !this.canRemoveToken(data)
                ? this.$t('wallet.disabled.delete_token')
                : this.$t('tooltip.remove');
        },
        showTooltipForCryptosAndTokens: function(data, option) {
            let disabledServices = transactionType.WITHDRAW === option
                ? this.isCryptoWithdrawalDisabled(data)
                : this.isCryptoDepositDisabled(data);
            let isItemOrUserBlocked = this.isUserBlocked;

            if (this.validTokens.includes(data.item)) {
                disabledServices = transactionType.WITHDRAW === option
                    ? this.isTokenWithdrawalDisabled(data)
                    : this.isTokenDepositDisabled(data);
                isItemOrUserBlocked = data.item.blocked;
            }

            return disabledServices
                && !this.viewOnly
                && !isItemOrUserBlocked;
        },
        tooltipDisabledWithdrawals: function(data) {
            return this.showTooltipForCryptosAndTokens(data, transactionType.WITHDRAW)
                ? this.$t('wallet.tooltip.disabled_deposits_and_withdrawals')
                : '';
        },
        tooltipDisabledDeposits: function(data) {
            return this.showTooltipForCryptosAndTokens(data, transactionType.DEPOSIT)
                ? this.$t('wallet.tooltip.disabled_deposits_and_withdrawals')
                : '';
        },
        tooltipDepositOrWithdrawButton: function(data, option) {
            if (!data.item.deployed && !data.item.blocked && this.validTokens.includes(data.item)) {
                return this.$t('wallet.disabled.deposit_and_withdraw');
            }

            return transactionType.WITHDRAW === option
                ? this.tooltipDisabledWithdrawals(data)
                : this.tooltipDisabledDeposits(data);
        },
        isDisabledCrypto: function(name) {
            return this.disabledCryptos.includes(name);
        },
        checkAllWithdrawDelays: async function() {
            try {
                const response = await this.$axios.single.get(this.$routing.generate('withdraw_delays'));

                for (const key of Object.keys(response.data)) {
                    const subject = response.data[key];

                    if (subject.passed) {
                        continue;
                    }

                    this.isWithdrawDelaysPassed = false;
                    this.notifyError(subject.errorMsg);
                    break;
                }
            } catch (e) {
                this.isWithdrawDelaysPassed = false;
                this.notifyError(this.$t('toasted.error.try_reload'));
            }
        },
        openWithdraw: async function(currency) {
            const isToken = !!this.tokens[currency];
            const isCrypto = !!this.predefinedTokens[currency];
            const isBlockedToken = isToken && this.tokens[currency].blocked;
            const action = this.getCryptoActionDisabled(currency, 'WithdrawalsDisabled');

            if (this.isDisabledCrypto(currency)
                || this.disabledServicesConfig[action]
                || (isToken && this.disabledServicesConfig.tokenWithdrawalsDisabled)
                || (isCrypto && this.disabledServicesConfig.coinWithdrawalsDisabled)
            ) {
                this.notifyError(this.$t('toasted.error.withdrawals.disabled'));

                return;
            }
            if ((isToken && isBlockedToken) || (!isToken && this.isUserBlocked )) {
                return;
            }

            if (!this.checkIfUserAbleToWithdraw('openWithdraw', currency)) {
                return;
            }

            await this.checkAllWithdrawDelays();

            if (!this.isWithdrawDelaysPassed) {
                this.isWithdrawDelaysPassed = true;

                return;
            }

            this.showModal = true;
            this.selectedCurrency = currency;
            this.isTokenModal = isToken;
            this.isOwner = isToken && this.tokens[currency].owner;
            this.isCreatedOnMintmeSite = isToken && this.tokens[currency].createdOnMintmeSite;
            this.isPausable = isToken && this.tokens[currency].pausable;

            if (isToken) {
                this.tokenImageUrl = this.tokens[currency].image.url;
                this.getTokenNetworks(currency);
            } else {
                this.getCryptoNetworks(currency);
            }
        },
        closeWithdraw: function(data) {
            this.showModal = false;

            if (data) {
                this.fetchTokens();
            }
        },
        fetchTokens: async function() {
            try {
                const response = await this.$axios.retry.get(this.$routing.generate('tokens', {
                    tokensInfo: true,
                    page: this.page,
                }));

                this.tableData = Object.assign({}, this.tableData, response.data.common);
                this.tokens = this.fillTokensWithInfo(this.tableData, response.data.tokensInfo);
                this.predefinedTokens = response.data.predefined;

                if (this.ownTokensCount <= Object.keys(this.tableData).length) {
                    this.allTokensLoaded = true;
                } else {
                    this.page++;
                }
            } catch (err) {
                this.serviceUnavailable = true;
                this.$logger.error('Can\'t fetch balances in Wallet page, tokens route', err);
                this.notifyError(this.$t('toasted.error.try_reload'));
            }
        },
        openDeposit: function(tradableSymbol) {
            const isToken = !!this.tokens[tradableSymbol];

            this.selectedToken = isToken ? this.tokens[tradableSymbol].name : null;
            this.selectedCurrency = tradableSymbol;
            this.isTokenModal = isToken;
            this.isOwner = isToken && this.tokens[tradableSymbol].owner;
            this.isCreatedOnMintmeSite = isToken && this.tokens[tradableSymbol].createdOnMintmeSite;
            this.selectedTokenHasTax = isToken && this.tokens[tradableSymbol].hasTax;

            if (isToken) {
                this.tokenImageUrl = this.tokens[tradableSymbol].image.url;
            }

            this.openDepositModal(tradableSymbol);
        },
        openDepositMore: function() {
            const isToken = !!this.tokens[this.depositMore];

            const asset = isToken ?
                this.tokens[this.depositMore] :
                this.predefinedTokens[this.depositMore]
            ;

            if (asset && !this.isUserBlocked) {
                if (window.history.replaceState) {
                    window.history.replaceState(
                        {}, '', location.href.split('?')[0]
                    );
                }

                this.openDepositModal(this.depositMore);
            }
        },
        updateBalances: function(data) {
            Object.keys(data).forEach((oTokenName) => {
                const oToken = data[oTokenName];

                Object.keys(this.predefinedTokens).forEach((token) => {
                    if (oTokenName === this.predefinedTokens[token].identifier) {
                        this.predefinedTokens[token].available = oToken.available;
                    }
                });

                Object.keys(this.tokens).forEach((token) => {
                    if (oTokenName === this.tokens[token].identifier) {
                        if (!this.tokens[token].owner) {
                            this.tokens[token].available = oToken.available;
                            return;
                        }

                        this.$axios.retry.get(this.$routing.generate('lock-period', {name: token}))
                            .then((res) =>
                                this.tokens[token].available = res.data ?
                                    new Decimal(oToken.available).sub(res.data.frozenAmountWithReceived)
                                    : oToken.available
                            )
                            .catch((err) => {
                                this.$logger.error('Can not get lock-period', err);
                            });
                    }
                });
            });
        },
        tokensToArray: function(tokens) {
            Object.keys(tokens).map(function(key) {
                tokens[key].name = key;
            });

            return Object.values(tokens);
        },
        fillTokensWithInfo: function(tokens, tokensInfo) {
            if (!tokens || !tokensInfo) {
                return tokens;
            }

            Object.keys(tokens).forEach((tokenName) => {
                tokens[tokenName].image = tokensInfo[tokenName]?.image || null;
                tokens[tokenName].depositsDisabled = tokensInfo[tokenName]?.depositsDisabled;
                tokens[tokenName].withdrawalsDisabled = tokensInfo[tokenName]?.withdrawalsDisabled;
            });

            return tokens;
        },
        generatePairUrl: function(market) {
            return this.$routing.generate('token_show_trade', {name: market.name});
        },
        generateCoinUrl: function(coin) {
            if (!coin.exchangeble || !coin.tradable) {
                return this.$routing.generate('coin', {
                    base: coin.tradable ? coin.name : webSymbol,
                    quote: coin.exchangeble ? coin.name : webSymbol,
                });
            }

            return this.$routing.generate('coin', {
                base: coin.name === webSymbol ? btcSymbol : coin.name,
                quote: webSymbol,
            });
        },
        canRemoveToken: function(data) {
            const amount = new Decimal(data.item.available);

            return !data.item.owner && (amount.lessThan(this.minAmount) || data.item.blocked);
        },
        openTokenDeleteModal: function(data) {
            if (!this.canRemoveToken(data)) {
                return;
            }

            this.showTokenDeleteModal = true;
            this.dataToDelete = data;
        },
        onTokenDeleteConfirm: function() {
            this.$axios.single.delete(this.$routing.generate('token_wallet_delete', {
                name: this.dataToDelete.item.name,
            }))
                .then((res) => {
                    this.notifySuccess(res.data.message);
                    location.reload();
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(err.message);
                    }
                    this.$logger.error('Can not delete token', err);
                });
        },
        generateTokenMarketNames: function() {
            if (null === this.tokens || null === this.predefinedTokens) {
                return [];
            }

            const tokenMarketNames = [];
            Object.values(this.tokens).forEach((token) => {
                Object.values(this.predefinedTokens).forEach((coin) => {
                    tokenMarketNames.push(token.identifier + coin.cryptoSymbol);
                });
            });

            return tokenMarketNames;
        },
        onWithdrawPhoneVerified() {
            this.withdrawAddPhoneModalVisible = false;

            if (this.withdrawFnToCallAfterVerification) {
                this[this.withdrawFnToCallAfterVerification.name](this.withdrawFnToCallAfterVerification.currency);
            }
        },
        getTokenNameTooltip(name) {
            return 17 < name.length
                ? {title: name, boundary: 'viewport'}
                : null;
        },
    },
};
</script>
