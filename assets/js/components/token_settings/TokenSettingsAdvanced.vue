<template>
    <div>
        <div v-if="isCreatedOnMintmeSite" class="card mt-2 px-3 py-3">
            <h5
                class="card-title"
                v-html="$t('page.token_settings.tab.advanced.initial_orders')"
            />
            <div class="row">
                <div class="col-12 col-md-7">
                    <initial-token-sell-orders
                        :config="initialSellOrderConfig"
                        :tokenName="getTokenName"
                    />
                </div>
                <div class="col">
                    <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                    {{ $t('token_init.create_orders.desc') }}
                </div>
            </div>
        </div>
        <div class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.advanced.social_media')"></h5>
            <div class="row">
                <div class="col-12 col-md-7">
                    <token-social-media-edit
                        editable
                        :discord-url="getSocialUrls.discordUrl"
                        :facebook-url="getSocialUrls.facebookUrl"
                        :facebook-app-id="facebookAppId"
                        :telegram-url="getSocialUrls.telegramUrl"
                        :website-url="getSocialUrls.websiteUrl"
                        :youtube-client-id="youtubeClientId"
                        :youtube-channel-id="getSocialUrls.youtubeChannelId"
                        :twitter-url="getSocialUrls.twitterUrl"
                        :token-name="getTokenName"
                        @updated-website="setWebsiteUrl"
                        @updated-facebook="setFacebookUrl"
                        @updated-youtube="setYoutubeChannelId"
                        @updated-discord="setDiscordUrl"
                        @updated-telegram="setTelegramUrl"
                        @updated-twitter="setTwitterUrl"
                    />
                </div>
                <div class="col">
                    <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                    {{ $t('page.token_settings.tab.advanced.social_media_tips') }}
                </div>
            </div>
        </div>
        <div v-if="showTokenReleaseAddress" class="card mt-2 px-3 py-3">
            <h5 class="card-title" v-html="$t('page.token_settings.tab.advanced.modify_address')"></h5>
            <div class="row">
                <div class="col-12 col-md-7">
                    <token-release-address
                        is-owner
                        :is-token-deployed="isTokenDeployed"
                        :release-address="releaseAddress"
                        :token-name="getTokenName"
                        :token-crypto="tokenCrypto"
                        :twofa="twofaEnabled"
                        :is-created-on-mintme-site="isCreatedOnMintmeSite"
                        :disabled-services-config="disabledServicesConfig"
                        :disabled-cryptos="disabledCryptos"
                        :is-user-blocked="isUserBlocked"
                    />
                </div>
                <div class="col">
                    <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                    {{ $t('page.token_settings.tab.advanced.modify_address_tips') }}
                </div>
            </div>
        </div>
        <div v-if="isCreatedOnMintmeSite" class="card mt-2 px-3 py-3">
            <div class="d-flex justify-content-center">
                <token-delete
                    :is-token-not-deployed="isTokenNotDeployed"
                    :token-name="getTokenName"
                    :twofa="twofaEnabled"
                />
            </div>
        </div>
    </div>
</template>

<script>
import TokenSocialMediaEdit from '../token/TokenSocialMediaEdit';
import TokenReleaseAddress from '../token/TokenReleaseAddress';
import TokenDelete from '../token/TokenDelete';
import {mapGetters, mapMutations} from 'vuex';
import {tokenDeploymentStatus} from '../../utils/constants';
import InitialTokenSellOrders from '../token/InitialTokenSellOrders';
import {generateMintmeAvatarHtml} from '../../utils';
export default {
    name: 'TokenSettingsAdvanced',
    props: {
        facebookAppId: String,
        youtubeClientId: String,
        releaseAddress: String,
        twofaEnabled: Boolean,
        websocketUrl: String,
        disabledServicesConfig: String,
        isCreatedOnMintmeSite: Boolean,
        currentLocale: String,
        explorerUrls: Object,
        initialSellOrderConfig: Object,
        disabledCryptos: Array,
        isUserBlocked: Boolean,
        tokenConnectEnabled: Boolean,
    },
    components: {
        InitialTokenSellOrders,
        TokenSocialMediaEdit,
        TokenReleaseAddress,
        TokenDelete,
    },
    computed: {
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
            'getMainDeploy',
        ]),
        ...mapGetters('tokenSettings', [
            'getTokenName',
            'getIsTokenExchanged',
            'getSocialUrls',
        ]),
        isTokenNotDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.getDeploymentStatus;
        },
        isTokenDeployed: function() {
            return tokenDeploymentStatus.deployed === this.getDeploymentStatus;
        },
        tokenCrypto() {
            return this.getMainDeploy
                ? this.getMainDeploy.crypto
                : null;
        },
        showTokenReleaseAddress: function() {
            return this.isCreatedOnMintmeSite && this.isTokenDeployed;
        },
        translationsContext: function() {
            return {
                mintmeBlock: generateMintmeAvatarHtml(),
            };
        },
    },
    methods: {
        ...mapMutations('tokenSettings', [
            'setFacebookUrl',
            'setWebsiteUrl',
            'setYoutubeChannelId',
            'setDiscordUrl',
            'setTelegramUrl',
            'setTwitterUrl',
        ]),
    },
};
</script>
