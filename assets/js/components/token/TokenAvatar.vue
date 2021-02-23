<template>
    <div class="token-avatar show-avatar ml-2">
        <div class="d-flex align-items-center token-name">
            <div class="align-items-center token-avatar-link">
                <avatar
                    type="token"
                    size="large"
                    :image="image"
                    :token="tokenName"
                    :editable="isOwner"
                />
            </div>
            <token-name
                class="d-flex align-items-center"
                :editable="isOwner"
                :has-release-period-prop="hasReleasePeriodProp"
                :is-token-created="isTokenCreated"
                :is-mintme-token="isMintmeToken"
                :identifier="market.quote.identifier"
                :name="market.quote.name"
                :precision="precision"
                :status-prop="statusProp"
                :twofa="twofa"
                :websocket-url="websocketUrl"
                :release-address="releaseAddress"
                :facebook-app-id="facebookAppId"
                :youtube-client-id="youtubeClientId"
                :website-url="tokenWebsite"
                :facebook-url="tokenFacebook"
                :youtube-channel-id="tokenYoutube"
                :telegram-url="telegramUrl"
                :discord-url="discordUrl"
                :airdrop-params="airdropParams"
                @token-deploy-pending="$emit('token-deploy-pending')"
                @updated-website="$emit('updated-website')"
                @updated-facebook="$emit('updated-facebook')"
                @updated-youtube="$emit('updated-youtube')"
                @updated-discord="$emit('updated-discord')"
                @updated-telegram="$emit('updated-telegram')"
                :show-token-edit-modal-prop="showTokenEditModal"
                :disabled-services-config="disabledServicesConfig"
            />
            <token-deploy-icon
                :is-mintme="isMintmeToken"
                :token-crypto="tokenCrypto"
                class="ml-2 token-deploy-icon"
                :is-owner="isOwner"
                :status-prop="statusProp"
            />
            <token-points-progress
                class="ml-2 token-point-progress"
                :profile-name="profileName"
                :profile-lastname="profileLastname"
                :profile-description="profileDescription"
                :profile-anonymously="profileAnonymously"
                :token-description="tokenDescription"
                :token-facebook="tokenFacebook"
                :token-youtube="tokenYoutube"
                :token-website="tokenWebsite"
                :token-status="statusProp"
                :is-mintme-token="isMintmeToken"
            />
        </div>
    </div>
</template>

<script>
import Avatar from '../Avatar';
import TokenName from './TokenName';
import TokenDeployIcon from './deploy/TokenDeployIcon';
import TokenPointsProgress from './TokenPointsProgress';

export default {
    name: 'TokenAvatar',
    props: {
        isOwner: Boolean,
        hasReleasePeriodProp: Boolean,
        isTokenCreated: Boolean,
        isMintmeToken: Boolean,
        market: Object,
        tokenCrypto: Object,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        websocketUrl: String,
        releaseAddress: String,
        airdropParams: Object,
        image: String,
        profileUrl: String,
        profileName: String,
        profileLastname: String,
        profileDescription: String,
        profileAnonymously: String,
        tokenDescription: String,
        facebookAppId: String,
        youtubeClientId: String,
        tokenFacebook: String,
        tokenYoutube: String,
        tokenWebsite: String,
        telegramUrl: String,
        discordUrl: String,
        showTokenEditModal: Boolean,
        disabledServicesConfig: String,
        tokenName: String,
    },
    components: {
        Avatar,
        TokenName,
        TokenDeployIcon,
        TokenPointsProgress,
    },
};
</script>
