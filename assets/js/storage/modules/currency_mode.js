export default {
    namespaced: true,
    state: {
        currencyMode: null,
    },
    getters: {
        getCurrencyMode(state) {
            return localStorage.getItem('_currency_mode');
            // return state.currencyMode;
        },
    },
    mutations: {
        setCurrencyMode(state, n) {
            state.currencyMode = n
        },
    },
};
