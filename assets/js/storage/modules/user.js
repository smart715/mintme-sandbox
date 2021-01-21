export default {
    namespaced: true,
    state: {
        isSignedInWithTwitter: false,
        id: null,
    },
    getters: {
        getIsSignedInWithTwitter(state) {
            return state.isSignedInWithTwitter;
        },
        getId(state) {
            return state.id;
        },
    },
    mutations: {
        setIsSignedInWithTwitter(state, n) {
            state.isSignedInWithTwitter = n;
        },
        setId(state, n) {
            state.id = n;
        },
    },
};
