<template>
    <div id="info-panel" class="position-relative">
        <div class="p-2">
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Username login/email">
                <b>{{ $t('info_bar.login.title') }}</b>  {{ username || 'guest' }}
            </span>
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Token name">
                <b>{{ $t('info_bar.token.title') }}</b> {{ infoData.tokenName || '-' }}
            </span>
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="MintMe balance">
                <b>{{ $t('info_bar.mintme.title') }}</b> {{ mintmeBalance }}
            </span>
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Etherium balance">
                <b>{{ $t('info_bar.eth.title') }}</b> {{ ethBalance }}
            </span>
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="USD Coin balance">
                <b>{{ $t('info_bar.usdc.title') }}</b> {{ usdcBalance }}
            </span>
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Bitcoin balance">
                <b>{{ $t('info_bar.btc.title') }}</b> {{ btcBalance }}
            </span>
            <span v-if="authCode" class="pr-2 pr-sm-5" v-b-tooltip.hover title="Current email verification code">
                <b>{{ $t('info_bar.code.title') }}</b> {{ authCode }}
            </span>
            <b-button v-b-toggle.collapse-3 class="btn-sm float-right mr-5 toggle-btn">{{ $t('info_bar.toggle.title') }}</b-button>
            <b-button
                v-if="'dev' !== environment && isIssueBranch"
                @click="manageBackendService"
                class="btn-sm float-right mr-4 toggle-btn"
                :disabled="null === backendServiceStatus || managingBackendService"
            >
                <font-awesome-icon
                    v-if="managingBackendService"
                    icon="circle-notch"
                    spin
                    class="loading-spinner" fixed-width
                />
                {{ getButtonName }}
            </b-button>
            <div class="close-btn p-sm-2" @click="close">
                <font-awesome-icon :icon="['fas', 'times-circle']"></font-awesome-icon>
            </div>
        </div>
        <b-collapse visible id="collapse-3">
            <div class="p-2">
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.branch.panel')">
                    <b>Pg:</b> {{ infoData.panelBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.branch.deposit')">
                    <b>Dg:</b> {{ infoData.depositBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.branch.contract')">
                    <b>Cg:</b> {{ infoData.contractBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.branch.withdraw')">
                    <b>Wg:</b> {{ infoData.withdrawBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.status.contract')">
                    <b>Tcg:</b>
                    <span :class="[infoData.isTokenContractActive ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.status.deposit')">
                    <b>Dcg:</b>
                    <span :class="[infoData.consumersInfo.deposit ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.status.withdraw')">
                    <b>Wcg:</b>
                    <span :class="[infoData.consumersInfo.payment ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.status.market')">
                    <b>Mcg:</b>
                    <span :class="[infoData.consumersInfo.market ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="this.$t('info_bar.status.deploy')">
                    <b>Dtcg:</b>
                    <span :class="[infoData.consumersInfo.deploy ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Status of token-contract-update consumer">
                    <b>Tcuc:</b>
                    <span :class="[infoData.consumersInfo['contract-update'] ? 'circle-info-on' : 'circle-info-off']"/>
                </span>
            </div>
        </b-collapse>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faTimesCircle} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import {BButton, BCollapse, VBTooltip, VBToggle} from 'bootstrap-vue';

library.add(faCircleNotch, faTimesCircle);

export default {
    name: 'InfoBar',
    components: {
        BButton,
        BCollapse,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
        'b-toggle': VBToggle,
    },
    props: {
        username: String,
        authCode: String,
        environment: String,
    },
    data() {
        return {
            isHidden: false,
            infoData: {
                tokenName: '-',
                panelBranch: '-',
                depositBranch: '-',
                contractBranch: '-',
                withdrawBranch: '-',
                consumersInfo: {
                    'deposit': null,
                    'payment': null,
                    'market': null,
                    'contract-update': null,
                },
                isTokenContractActive: false,
            },
            backendServiceStatus: null,
            managingBackendService: false,
            balance: {
                WEB: null,
                BTC: null,
                ETH: null,
                USDC: null,
            },
            interval: null,
        };
    },
    created() {
        this.fetchBackendServiceStatus();
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('hacker_info'))
            .then((res) => {
                this.infoData = res.data;
            });
        if (this.username) {
            this.fetchBalance();
            this.interval = setInterval(this.fetchBalance, 10000);
        }
    },
    computed: {
        isIssueBranch: function() {
            return ('-' === this.infoData.panelBranch || !this.infoData.panelBranch.startsWith('v'));
        },
        getButtonName: function() {
            if (this.managingBackendService) {
                return this.$t('info_bar.backend_service.in_progress');
            } else if (!this.backendServiceStatus) {
                return this.$t('info_bar.backend_service.create');
            } else {
                return this.$t('info_bar.backend_service.delete');
            }
        },
        mintmeBalance: function() {
            return this.balance.WEB ? new Decimal(this.balance.WEB.available).toFixed(8) : '-';
        },
        ethBalance: function() {
            return this.balance.ETH ? new Decimal(this.balance.ETH.available).toFixed(8) : '-';
        },
        btcBalance: function() {
            return this.balance.BTC ? new Decimal(this.balance.BTC.available).toFixed(this.balance.BTC.subunit) : '-';
        },
        usdcBalance: function() {
          return this.balance.USDC ? new Decimal(this.balance.USDC.available).toFixed(this.balance.USDC.subunit) : '-';
        },
    },
    methods: {
        manageBackendService: function() {
           !this.backendServiceStatus ?
                this.createBackendServices() :
                this.deleteBackendServices();
        },
        fetchBackendServiceStatus: function() {
            this.managingBackendService = true;
            this.$axios.retry.get(this.$routing.generate('status_container'))
                .then((res) => {
                    this.backendServiceStatus = res.data;
                    this.managingBackendService = false;
                });
        },
        createBackendServices: function() {
            this.managingBackendService = true;
            this.$axios.retry.post(this.$routing.generate('create_container'))
                .then((res) => {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                });
        },
        deleteBackendServices: function() {
            this.managingBackendService = true;
            this.$axios.retry.post(this.$routing.generate('delete_container'))
                .then((res) => {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                });
        },
        fetchBalance: function() {
            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    this.balance = res.data.predefined;
                });
        },
        close: function() {
            this.$axios.retry.get(this.$routing.generate('hacker-toggle-info-bar')).catch(() => {});
            if (this.interval) {
                clearInterval(this.interval);
            }
            this.$el.parentElement.removeChild(this.$el);
            this.$destroy();
        },
    },
};
</script>

<style lang="scss">
@import '../../scss/variables';

#info-panel {
    background-color: #01579B;
    line-height: 18px;
}

.circle-info {
    height: 10px;
    width: 10px;
    background-color: $grey-light;
    border-radius: 50%;
    display: inline-block;
}

.circle-info-off {
    @extend .circle-info;
    background-color: red;
}

.circle-info-on {
    @extend .circle-info;
    background-color: green;
}
.resize-btn {
    position: absolute;
    right: 0;
    bottom: 0;
}

.toggle-btn {
    line-height: 13px;
}

.close-btn {
    position: absolute;
    right: 9px;
    top: 2px;
}
</style>
