<template>
    <a
        :href="tokenSettingsUrl"
        :class="{'c-default': !isAllowedToVisitSettings}"
        @click="checkUser"
    >
        <div v-if="deployed" class="justify-content-center deployed-icon-container">
            <guide class-prop="d-inline-block" tippy-class="d-inline-block">
                <template slot="icon">
                    <img
                        class="deployed-icon"
                        :src="deployedImg"
                        :alt="this.$t('token.deploy_icon.img_alt.deployed')"
                    >
                </template>
                <template slot="body">
                    {{ deployedBodyText }}
                </template>
            </guide>
        </div>
        <div v-else-if="showPending">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-else-if="notDeployed">
            <guide class-prop="d-inline-block" tippy-class="d-inline-block">
                <template slot="icon">
                    <img
                        class="not-deployed-icon"
                        :src="getWebIconPath"
                        :alt="this.$t('token.deploy_icon.img_alt.not_deployed')"
                    >
                </template>
                <template slot="body">
                    {{ $t('token.deploy_icon.doesnt_exist_on_blockchain') }}
                </template>
            </guide>
        </div>
    </a>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';
import {
    tokenDeploymentStatus,
    BNB,
    WEB,
} from '../../../utils/constants';
import {getCoinAvatarAssetName} from '../../../utils';
import {BlockchainShortNameMixin} from '../../../mixins';

library.add(faCircleNotch);

export default {
    name: 'TokenDeployIcon',
    mixins: [
        BlockchainShortNameMixin,
    ],
    components: {
        Guide,
        FontAwesomeIcon,
    },
    props: {
        isMintme: Boolean,
        tokenCrypto: Object,
        isOwner: Boolean,
        statusProp: String,
        tokenName: String,
        loggedIn: Boolean,
    },
    computed: {
        deployed: function() {
            return tokenDeploymentStatus.deployed === this.statusProp;
        },
        notDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.statusProp;
        },
        showPending: function() {
            return this.isOwner && tokenDeploymentStatus.pending === this.statusProp;
        },
        deployedImg: function() {
            return require(`../../../../img/${getCoinAvatarAssetName(this.tokenCrypto.symbol)}`);
        },
        deployedBodyText: function() {
            return this.isMintme
                ? this.$t('token.deploy_icon.body')
                : this.$t('token.deploy_icon.body_not_mintme', {
                    baseName: this.cryptoChain,
                });
        },
        cryptoChain: function() {
            if (BNB.symbol === this.tokenCrypto.symbol) {
                return 'Binance Smart Chain';
            }

            // TODO: invent proper way of displaying blockchain name
            return this.getBlockchainShortName(this.tokenCrypto.symbol) === this.tokenCrypto.symbol
                ? this.tokenCrypto.name
                : this.getBlockchainShortName(this.tokenCrypto.symbol);
        },
        getWebIconPath: function() {
            return require(`../../../../img/${WEB.icon}`);
        },
        tokenSettingsUrl() {
            return this.isAllowedToVisitSettings
                ? this.$routing.generate('token_settings', {tokenName: this.tokenName, tab: 'advanced'})
                : '#';
        },
        isAllowedToVisitSettings() {
            return this.loggedIn && this.isOwner;
        },
    },
    methods: {
        checkUser(e) {
            if (!this.isAllowedToVisitSettings) {
                e.preventDefault();
            }
        },
    },
};
</script>
