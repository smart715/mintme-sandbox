export default {
    namespaced: true,
    state: {
        currencyMode: null,
    },
    getters: {
        getCurrencyMode(state) {
            return state.currencyMode;
        },
    },
    mutations: {
        setCurrencyMode(state, n) {
            state.currencyMode = n;
        },
    },
};
