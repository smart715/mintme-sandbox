<template>
    <div>
        <div v-if="isCreatedOnMintmeSite" class="card mt-2 px-3 py-3">
            <template v-if="!isTokenDeployed">
                <h5 class="card-title" v-html="$t('page.token_settings.tab.advanced.deploy')"></h5>
                <div class="row">
                    <div class="col-12 col-md-7">
                        <token-deploy
                            is-owner
                            :name="getTokenName"
                            :status-prop="getDeploymentStatus"
                            :websocket-url="websocketUrl"
                            :disabled-services-config="disabledServicesConfig"
                            :token-crypto="tokenCrypto"
                            :is-created-on-mintme-site="isCreatedOnMintmeSite"
                            :disabled-cryptos="disabledCryptos"
                            @click-release-period="clickReleasePeriod"
                            @pending="$emit('token-deploy-pending', $event)"
                        />
                    </div>
                    <div class="col">
                        <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                        <span v-html="$t('token.deploy.final_step', translationsContext)" />
                    </div>
                </div>
            </template>
            <template v-else>
                <h5 class="card-title" v-html="$t('page.token_settings.tab.advanced.connect')"></h5>
                <div class="row">
                    <div class="col-12 col-md-7">
                        <token-connect
                            is-owner
                            :tokenName="getTokenName"
                            :deploy-crypto="tokenCrypto"
                            :disabled-services-config="disabledServicesConfig"
                            :current-locale="currentLocale"
                            :explorer-urls="explorerUrls"
                            :is-created-on-mintme-site="isCreatedOnMintmeSite"
                            :disabled-cryptos="disabledCryptos"
                            :enabled="tokenConnectEnabled"
                            @pending="$emit('token-deploy-pending', $event)"
                        />
                    </div>
                    <div class="col">
                        <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                        {{ $t('token.connect.info') }}
                    </div>
                </div>
            </template>
        </div>
        <div v-if="isCreatedOnMintmeSite" class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.advanced.release_period')"></h5>
            <div class="row">
                <div class="col-12 col-md-7">
                    <token-release-period
                        ref="token-release-period-component"
                        :is-token-exchanged="getIsTokenExchanged"
                        :is-token-not-deployed="!isTokenDeployed"
                        :token-name="getTokenName"
                    />
                </div>
                <div class="col">
                    <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                    {{ $t('token.release_period.body') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import TokenConnect from '../token/deploy/TokenConnect';
import TokenDeploy from '../token/deploy/TokenDeploy';
import TokenReleasePeriod from '../token/TokenReleasePeriod';
import {mapGetters} from 'vuex';
import {tokenDeploymentStatus} from '../../utils/constants';
import {generateMintmeAvatarHtml} from '../../utils';

export default {
    name: 'TokenSettingsDeploy',
    props: {
        websocketUrl: String,
        disabledServicesConfig: String,
        isCreatedOnMintmeSite: Boolean,
        hasReleasePeriod: Boolean,
        currentLocale: String,
        explorerUrls: Object,
        disabledCryptos: Array,
        tokenConnectEnabled: Boolean,
    },
    components: {
        TokenDeploy,
        TokenConnect,
        TokenReleasePeriod,
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
            'getMainDeploy',
        ]),
        ...mapGetters('tokenSettings', [
            'getTokenName',
            'getIsTokenExchanged',
        ]),
        isTokenDeployed: function() {
            return tokenDeploymentStatus.deployed === this.getDeploymentStatus;
        },
        tokenCrypto() {
            return this.getMainDeploy
                ? this.getMainDeploy.crypto
                : null;
        },
        translationsContext: function() {
            return {
                mintmeBlock: generateMintmeAvatarHtml(),
            };
        },
    },
    methods: {
        clickReleasePeriod() {
            this.$refs['token-release-period-component']?.$el.scrollIntoView(
                {
                    behavior: 'smooth',
                    block: 'center',
                }
            );
        },
    },
};
</script>
