export default {
    namespaced: true,
    state: {
        isPostsInitialized: false,
        isRewardsInitialized: false,
    },
    getters: {
        getIsPostsInitialized(state) {
            return state.isPostsInitialized;
        },
        getIsRewardsInitialized(state) {
            return state.isRewardsInitialized;
        },
    },
    mutations: {
        setIsPostsInitialized(state, payload) {
            state.isPostsInitialized = payload;
        },
        setIsRewardsInitialized(state, payload) {
            state.isRewardsInitialized = payload;
        },
    },
};
