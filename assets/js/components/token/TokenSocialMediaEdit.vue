<template>
    <div class="pb-1 pt-1 flex-grow-1 mb-2 token-social-media-edit">
        <token-website-address
            :currentWebsite="currentWebsite"
            :editingWebsite="editingWebsite"
            :tokenName="tokenName"
            :key="reRenderTokenWebsite"
            @saveWebsite="saveWebsite"
            @toggleEdit="toggleEdit"
        />
        <token-youtube-address
            :editable="editable"
            :channel-id="currentYoutube"
            :client-id="youtubeClientId"
            :tokenName="tokenName"
            @saveYoutube="saveYoutube"
        />
        <token-facebook-address
            :address="currentFacebook"
            :app-id="facebookAppId"
            :editing="editingUrls"
            :tokenName="tokenName"
            @saveFacebook="saveFacebook"
        />
        <token-telegram-channel
            :currentTelegram="currentTelegram"
            :editingTelegram="editingTelegram"
            :tokenName="tokenName"
            @saveTelegram="saveTelegram"
            @toggleEdit="toggleEdit"
        />
        <token-discord-channel
            :currentDiscord="currentDiscord"
            :editingDiscord="editingDiscord"
            :tokenName="tokenName"
            @saveDiscord="saveDiscord"
            @toggleEdit="toggleEdit"
        />
    </div>
</template>

<script>
import TokenDiscordChannel from './TokenDiscordChannel';
import TokenFacebookAddress from './facebook/TokenFacebookAddress';
import TokenTelegramChannel from './TokenTelegramChannel';
import TokenWebsiteAddress from './website/TokenWebsiteAddress';
import TokenYoutubeAddress from './youtube/TokenYoutubeAddress';

export default {
    name: 'TokenSocialMediaEdit',
    props: {
        discordUrl: String,
        editable: Boolean,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        websiteUrl: String,
        youtubeClientId: String,
        youtubeChannelId: String,
        tokenName: String,
    },
    components: {
        TokenDiscordChannel,
        TokenFacebookAddress,
        TokenTelegramChannel,
        TokenYoutubeAddress,
        TokenWebsiteAddress,
    },
    data() {
        return {
            currentDiscord: this.discordUrl,
            currentFacebook: this.facebookUrl,
            currentTelegram: this.telegramUrl,
            currentWebsite: this.websiteUrl,
            currentYoutube: this.youtubeChannelId,
            editingDiscord: false,
            editingTelegram: false,
            editingUrls: false,
            editingWebsite: false,
            reRenderTokenWebsite: 0,
        };
    },
    methods: {
        saveWebsite: function(newWebsite) {
            this.currentWebsite = newWebsite;
            this.$emit('updated-website', newWebsite);
            this.reRenderTokenWebsite++;
            this.editingWebsite = false;
        },
        saveDiscord: function(newDiscord) {
            this.currentDiscord = newDiscord;
            this.$emit('updated-discord', newDiscord);
        },
        saveFacebook: function(newFacebook) {
            this.currentFacebook = newFacebook;
            this.$emit('updated-facebook', newFacebook);
        },
        saveTelegram: function(newTelegram) {
            this.currentTelegram = newTelegram;
            this.$emit('updated-telegram', newTelegram);
        },
        saveYoutube: function(newChannelId) {
            this.currentYoutube = newChannelId;
            this.$emit('updated-youtube', newChannelId);
        },
        toggleEdit: function(url = null) {
            this.editingDiscord = 'discord' === url;
            this.editingTelegram = 'telegram' === url;
            this.editingWebsite = 'website' === url;
        },
    },
};
</script>
