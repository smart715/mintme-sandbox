<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :without-padding="true"
            @close="$emit('close')"
        >
            <template slot="header"><p class="word-break-all">{{ currentName }}</p></template>
            <template slot="body">
                <div class="token-edit p-0">
                    <div class="row faq-block mx-0 border-bottom border-top">
                        <faq-item @switch="refreshSliders">
                            <template slot="title">
                                Add social media
                            </template>
                            <template slot="body">
                                <token-social-media-edit
                                    :discord-url="discordUrl"
                                    :editable="editable"
                                    :facebook-url="facebookUrl"
                                    :facebook-app-id="facebookAppId"
                                    :telegram-url="telegramUrl"
                                    :website-url="websiteUrl"
                                    :youtube-client-id="youtubeClientId"
                                    :youtube-channel-id="youtubeChannelId"
                                    :token-name="currentName"
                                    @updated-website="$emit('updated-website', $event)"
                                    @updated-facebook="$emit('updated-facebook', $event)"
                                    @updated-youtube="$emit('updated-youtube', $event)"
                                    @updated-discord="$emit('updated-discord', $event)"
                                    @updated-telegram="$emit('updated-telegram', $event)"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item @switch="refreshSliders">
                            <template slot="title">
                                Token release period
                            </template>
                            <template slot="body">
                                <token-release-period
                                    ref="token-release-period-component"
                                    :is-token-exchanged="isTokenExchanged"
                                    :is-token-not-deployed="isTokenNotDeployed"
                                    :token-name="currentName"
                                    @update="releasePeriodUpdated"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Deploy token to blockchain
                            </template>
                            <template slot="body">
                                <token-deploy
                                    :has-release-period="hasReleasePeriod"
                                    :is-owner="isOwner"
                                    :name="currentName"
                                    :precision="precision"
                                    :status-prop="statusProp"
                                    :websocket-url="websocketUrl"
                                    @pending="$emit('token-deploy-pending')"
                                    :key="tokenDeployKey"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div
                        v-if="isTokenCreated && isOwner"
                        class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Airdrop campaign
                            </template>
                            <template slot="body">
                                <token-airdrop-campaign
                                    :token-name="currentName"
                                    :airdrop-params="airdropParams"
                                    @close="$emit('close')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Change token name
                            </template>
                            <template slot="body">
                                <token-change-name
                                    :is-token-exchanged="isTokenExchanged"
                                    :is-token-not-deployed="isTokenNotDeployed"
                                    :current-name="currentName"
                                    :twofa="twofa"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0 border-bottom">
                        <faq-item>
                            <template slot="title">
                                Modify token release address
                            </template>
                            <template slot="body">
                                <token-release-address
                                    :is-token-deployed="isTokenDeployed"
                                    :release-address="releaseAddress"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                    @update-release-address="$emit('update-release-address')"
                                />
                            </template>
                        </faq-item>
                    </div>
                    <div class="row faq-block mx-0">
                        <faq-item>
                            <template slot="title">
                                Delete token
                            </template>
                            <template slot="body">
                                <token-delete
                                    :is-token-exchanged="isTokenExchanged"
                                    :is-token-not-deployed="isTokenNotDeployed"
                                    :token-name="currentName"
                                    :twofa="twofa"
                                />
                            </template>
                        </faq-item>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import FaqItem from '../FaqItem';
import Guide from '../Guide';
import Modal from './Modal';
import TokenChangeName from '../token/TokenChangeName';
import TokenAirdropCampaign from '../token/airdrop_campaign/TokenAirdropCampaign';
import TokenDelete from '../token/TokenDelete';
import TokenDeploy from '../token/deploy/TokenDeploy';
import TokenSocialMediaEdit from '../token/TokenSocialMediaEdit';
import TokenReleaseAddress from '../token/TokenReleaseAddress';
import TokenReleasePeriod from '../token/TokenReleasePeriod';
import TwoFactorModal from './TwoFactorModal';
import {tokenDeploymentStatus} from '../../utils/constants';

export default {
    name: 'TokenEditModal',
    components: {
        FaqItem,
        Guide,
        Modal,
        TokenChangeName,
        TokenAirdropCampaign,
        TokenDelete,
        TokenDeploy,
        TokenReleaseAddress,
        TokenReleasePeriod,
        TokenSocialMediaEdit,
        TwoFactorModal,
    },
    props: {
        currentName: String,
        hasReleasePeriodProp: Boolean,
        isOwner: Boolean,
        isTokenCreated: Boolean,
        isTokenExchanged: Boolean,
        noClose: Boolean,
        precision: Number,
        releaseAddress: String,
        statusProp: String,
        twofa: Boolean,
        visible: Boolean,
        websocketUrl: String,
        airdropParams: Object,
        discordUrl: String,
        editable: Boolean,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        websiteUrl: String,
        youtubeClientId: String,
        youtubeChannelId: String,
    },
    data() {
        return {
            hasReleasePeriod: this.hasReleasePeriodProp,
            tokenDeployKey: 0,
        };
    },
    beforeUpdate: function() {
        if (this.isTokenDeployed) {
            this.tokenDeployKey++;
        };
    },
    computed: {
        isTokenNotDeployed: function() {
            return tokenDeploymentStatus.notDeployed === this.statusProp;
        },
        isTokenDeployed: function() {
            return tokenDeploymentStatus.deployed === this.statusProp;
        },
    },
    methods: {
        releasePeriodUpdated: function() {
            this.hasReleasePeriod = true;
        },
        refreshSliders: function() {
            this.$refs['token-release-period-component'].$refs['released-slider'].refresh();
            this.$refs['token-release-period-component'].$refs['release-period-slider'].refresh();
        },
    },
};
</script>
