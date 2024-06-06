<template>
    <div id="info-panel" class="position-relative">
        <div class="p-2">
            <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Username login/email">
                <b>{{ $t('info_bar.login.title') }}</b>  {{ username || 'guest' }}
            </span>
            <template v-if="!!username">
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover title="Token name">
                    <b>{{ $t('info_bar.token.title') }}</b> {{ infoData.tokenName || '-' }}
                </span>

                <span
                    v-for="balance in balanceArray"
                    :key="balance.cryptoSymbol"
                    class="pr-2 pr-sm-5"
                    v-b-tooltip.hover
                    :title="balance.fullname | rebranding"
                >
                    <b>{{ balance.cryptoSymbol | rebranding }}:</b>
                    {{ balance.available | toMoney(balance.subunit) }}
                </span>
            </template>
            <span v-if="authCode" class="pr-2 pr-sm-5" v-b-tooltip.hover title="Current email verification code">
                <b>{{ $t('info_bar.code.title') }}</b> {{ authCode }}
            </span>
            <b-button v-b-toggle.collapse-infobar class="btn-sm float-right mr-5 toggle-btn">
                {{ $t('info_bar.toggle.title') }}
            </b-button>
            <b-button
                v-if="'dev' !== environment"
                @click="manageBackendService"
                class="btn-sm float-right mr-4 toggle-btn"
                :disabled="manageBackendDisabled"
            >
                <font-awesome-icon
                    v-if="managingBackendService"
                    icon="circle-notch"
                    class="loading-spinner"
                    spin
                    fixed-width
                />
                {{ getButtonName }}
            </b-button>
            <div class="close-btn p-sm-2" @click="close">
                <font-awesome-icon :icon="['fas', 'times-circle']"></font-awesome-icon>
            </div>
        </div>
        <b-collapse visible id="collapse-infobar">
            <div class="p-2">
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.branch.panel')">
                    <b>Panel:</b> {{ infoData.panelBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.branch.gateway')">
                    <b>Gateway:</b> {{ infoData.gatewayBranch }}
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.gateway')">
                    <b>Gs:</b>
                    <span :class="getStatusService(infoData.isGatewayActive)"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.deposit')">
                    <b>Dcg:</b>
                    <span :class="getStatusService(infoData.consumersInfo.deposit)"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.withdraw')">
                    <b>Wcg:</b>
                    <span :class="getStatusService(infoData.consumersInfo.payment)"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.market')">
                    <b>Mcg:</b>
                    <span :class="getStatusService(infoData.consumersInfo.market)"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.deploy')">
                    <b>Dtcg:</b>
                    <span :class="getStatusService(infoData.consumersInfo.deploy)"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.token_contract_update')">
                    <b>Tcuc:</b>
                    <span :class="getStatusService(infoData.consumersInfo['contract-update'])"/>
                </span>
                <span class="pr-2 pr-sm-5" v-b-tooltip.hover :title="$t('info_bar.status.email_consumer')">
                    <b>EC:</b>
                    <span :class="getStatusService(infoData.consumersInfo['panel-email'])"/>
                </span>
            </div>
        </b-collapse>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faTimesCircle} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {BButton, BCollapse, VBTooltip, VBToggle} from 'bootstrap-vue';
import {MoneyFilterMixin, RebrandingFilterMixin} from '../mixins';

library.add(faCircleNotch, faTimesCircle);

export default {
    name: 'InfoBar',
    components: {
        BButton,
        BCollapse,
        FontAwesomeIcon,
    },
    mixins: [
        MoneyFilterMixin,
        RebrandingFilterMixin,
    ],
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
                gatewayBranch: '-',
                consumersInfo: {
                    'deposit': null,
                    'payment': null,
                    'market': null,
                    'contract-update': null,
                    'panel-email': null,
                },
                isGatewayActive: false,
            },
            backendServiceStatus: null,
            managingBackendService: false,
            balances: {},
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
        balanceArray: function() {
            return Object.values(this.balances);
        },
        manageBackendDisabled: function() {
            return null === this.backendServiceStatus ||
                this.managingBackendService ||
                (!this.isIssueBranch && this.backendServiceStatus);
        },
        isIssueBranch: function() {
            return ('-' === this.infoData.panelBranch || !this.infoData.panelBranch.match('^v[0-9]+$'));
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
    },
    methods: {
        getStatusService: function(service) {
            return (this.backendServiceStatus || 'dev' === this.environment) && service ?
                'circle-info-on' :
                'circle-info-off';
        },
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
                    this.balances = res.data.predefined;
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
    background-color: $infobar-bg;
    line-height: 18px;
}

.circle-info {
    height: 10px;
    width: 10px;
    background-color: $infobar-circle-bg;
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
