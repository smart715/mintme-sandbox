<template>
    <div class="pb-1 pt-1 flex-grow-1 mb-2 token-social-media-edit">
        <token-website-address
            :currentWebsite="currentWebsite"
            :editingWebsite="editingWebsite"
            :tokenName="tokenName"
            :key="reRenderTokenWebsite"
            @saveWebsite="saveWebsite"
            @toggleEdit="toggleEdit"
            class="mb-2"
        />
        <token-twitter-address
            :address="currentTwitter"
            :tokenName="tokenName"
            @saveTwitter="saveTwitter"
            class="mb-2"
        />
        <token-youtube-address
            :editable="editable"
            :channel-id="currentYoutube"
            :client-id="youtubeClientId"
            :tokenName="tokenName"
            @saveYoutube="saveYoutube"
            class="mb-2"
        />
        <token-facebook-address
            :address="currentFacebook"
            :app-id="facebookAppId"
            :editing="editingUrls"
            :tokenName="tokenName"
            @saveFacebook="saveFacebook"
            class="mb-2"
        />
        <token-telegram-channel
            :currentTelegram="currentTelegram"
            :editingTelegram="editingTelegram"
            :tokenName="tokenName"
            @saveTelegram="saveTelegram"
            @toggleEdit="toggleEdit"
            class="mb-2"
        />
        <token-discord-channel
            :currentDiscord="currentDiscord"
            :editingDiscord="editingDiscord"
            :tokenName="tokenName"
            @saveDiscord="saveDiscord"
            @toggleEdit="toggleEdit"
            class="mb-2"
        />
    </div>
</template>

<script>
import TokenDiscordChannel from './TokenDiscordChannel';
import TokenFacebookAddress from './facebook/TokenFacebookAddress';
import TokenTelegramChannel from './TokenTelegramChannel';
import TokenWebsiteAddress from './website/TokenWebsiteAddress';
import TokenYoutubeAddress from './youtube/TokenYoutubeAddress';
import TokenTwitterAddress from './twitter/TokenTwitterAddress';

export default {
    name: 'TokenSocialMediaEdit',
    props: {
        discordUrl: String,
        editable: Boolean,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        websiteUrl: String,
        twitterUrl: String,
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
        TokenTwitterAddress,
    },
    data() {
        return {
            currentDiscord: this.discordUrl,
            currentFacebook: this.facebookUrl,
            currentTelegram: this.telegramUrl,
            currentWebsite: this.websiteUrl,
            currentYoutube: this.youtubeChannelId,
            currentTwitter: this.twitterUrl,
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
        saveTwitter: function(newTwitter) {
            this.currentTwitter = newTwitter;
            this.$emit('updated-twitter', newTwitter);
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
