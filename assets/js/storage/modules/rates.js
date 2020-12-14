export default {
    namespaced: true,
        state: {
            requesting: false,
            rates: {},
            globalCurrencyMode: null,
    },
    getters: {
        getGlobalCurrencyMode(state) {
            return state.globalCurrencyMode;
        },
        getRequesting(state) {
            return state.requesting;
        },
        getRates(state) {
            return state.rates;
        },
    },
    mutations: {
        setGlobalCurrencyMode(state, n) {
            state.globalCurrencyMode = n;
        },
        setRequesting(state, n) {
            state.requesting = n;
        },
        setRates(state, n) {
            state.rates = n;
        },
    },
};
