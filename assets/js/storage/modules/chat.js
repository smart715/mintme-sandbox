const storage = {
    namespaced: true,
    state: {
        contactName: null,
        tokenName: null,
        userTokenName: null,
        currentThreadId: 0,
        dMMinAmount: null,
        rankImg: null,
    },
    getters: {
        getContactName: function(state) {
            return state.contactName;
        },
        getTokenName: function(state) {
            return state.tokenName;
        },
        getUserTokenName: function(state) {
            return state.userTokenName;
        },
        getCurrentThreadId: function(state) {
            return state.currentThreadId;
        },
        getDMMinAmount: function(state) {
            return state.dMMinAmount;
        },
        getRankImg: function(state) {
            return state.rankImg;
        },
    },
    mutations: {
        setContactName: function(state, n) {
            state.contactName = n;
        },
        setTokenName: function(state, n) {
            state.tokenName = n;
        },
        setUserTokenName: function(state, n) {
            state.userTokenName = n;
        },
        setCurrentThreadId: function(state, n) {
            state.currentThreadId = n;
        },
        setDMMinAmount: function(state, n) {
            state.dMMinAmount = n;
        },
        setRankImg: function(state, n) {
            state.rankImg = n;
        },
    },
};

export default storage;
