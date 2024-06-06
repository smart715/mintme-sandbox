export default {
    namespaced: true,
    state: {
        isSignedInWithTwitter: false,
        isAuthorizedYoutube: false,
        hasPhoneVerified: false,
        isPhoneVerificationPending: false,
        id: null,
        nickname: null,
        ownDeployedTokens: [],
        depositPhoneRequired: false,
        withdrawalPhoneRequired: false,
    },
    getters: {
        getIsSignedInWithTwitter(state) {
            return state.isSignedInWithTwitter;
        },
        getIsAuthorizedYoutube(state) {
            return state.isAuthorizedYoutube;
        },
        getId(state) {
            return state.id;
        },
        getNickname(state) {
            return state.nickname;
        },
        getHasPhoneVerified(state) {
            return state.hasPhoneVerified;
        },
        getIsPhoneVerificationPending(state) {
            return state.isPhoneVerificationPending;
        },
        getOwnDeployedTokens(state) {
            return state.ownDeployedTokens;
        },
        getDepositPhoneRequired(state) {
            return state.depositPhoneRequired;
        },
        getWithdrawalPhoneRequired(state) {
            return state.withdrawalPhoneRequired;
        },
    },
    mutations: {
        setIsSignedInWithTwitter(state, n) {
            state.isSignedInWithTwitter = n;
        },
        setIsAuthorizedYoutube(state, n) {
            state.isAuthorizedYoutube = n;
        },
        setId(state, n) {
            state.id = n;
        },
        setNickname(state, payload) {
            state.nickname = payload;
        },
        setHasPhoneVerified(state, payload) {
            state.hasPhoneVerified = payload;
        },
        setIsPhoneVerificationPending(state, payload) {
            state.isPhoneVerificationPending = payload;
        },
        setOwnDeployedTokens(state, payload) {
            state.ownDeployedTokens = payload;
        },
        setDepositPhoneRequired(state, payload) {
            state.depositPhoneRequired = payload;
        },
        setWithdrawalPhoneRequired(state, payload) {
            state.withdrawalPhoneRequired = payload;
        },
    },
};
