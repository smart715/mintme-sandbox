const storage = {
    namespaced: true,
    state: {
        tokenName: null,
        tokenAvatar: null,
        isTokenExchanged: null,
        socialUrls: {
            facebookUrl: null,
            websiteUrl: null,
            youtubeChannelId: null,
            discordUrl: null,
            telegramUrl: null,
            twitterUrl: null,
        },
        hasReleasePeriod: false,
        isCreatedOnMintmeSite: true,
    },
    getters: {
        getTokenName: function(state) {
            return state.tokenName;
        },
        getTokenAvatar: function(state) {
            return state.tokenAvatar;
        },
        getIsTokenExchanged: function(state) {
            return state.isTokenExchanged;
        },
        getSocialUrls: function(state) {
            return state.socialUrls;
        },
        getIsCreatedOnMintmeSite: function(state) {
            return state.isCreatedOnMintmeSite;
        },
    },
    mutations: {
        setActiveTab: function(state, payload) {
            state.activeTab = payload;
        },
        setTokenName: function(state, payload) {
            state.tokenName = payload;
        },
        setTokenAvatar: function(state, payload) {
            state.tokenAvatar = payload;
        },
        setIsTokenExchanged: function(state, payload) {
            state.isTokenExchanged = payload;
        },
        setSocialUrls: function(state, payload) {
            state.socialUrls = payload;
        },
        setIsCreatedOnMintmeSite: function(state, payload) {
            state.isCreatedOnMintmeSite = payload;
        },
        setFacebookUrl: function(state, payload) {
            state.socialUrls.facebookUrl = payload;
        },
        setWebsiteUrl: function(state, payload) {
            state.socialUrls.websiteUrl = payload;
        },
        setYoutubeChannelId: function(state, payload) {
            state.socialUrls.youtubeChannelId = payload;
        },
        setDiscordUrl: function(state, payload) {
            state.socialUrls.discordUrl = payload;
        },
        setTelegramUrl: function(state, payload) {
            state.socialUrls.telegramUrl = payload;
        },
        setTwitterUrl: function(state, payload) {
            state.socialUrls.twitterUrl = payload;
        },
        setHasReleasePeriod: function(state, payload) {
            state.hasReleasePeriod = payload;
        },
    },
};

export default storage;
