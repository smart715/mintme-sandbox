const storage = {
    namespaced: true,
    state: {
        cryptosMap: {},
    },
    getters: {
        getCryptos: function(state) {
            return Object.values(state.cryptosMap);
        },
        getCryptosMap: function(state) {
            return state.cryptosMap;
        },
    },
    mutations: {
        setCryptos: function(state, payload) {
            state.cryptosMap = payload.reduce((acc, crypto) => {
                acc[crypto.symbol] = crypto;

                return acc;
            }, {});
        },
        setCrypto: function(state, {symbol, crypto}) {
            state.cryptosMap[symbol] = crypto;
        },
    },
    actions: {
        updateCrypto: async function({commit}, symbol) {
            const response = await this._vm.$axios.retry.get(this._vm.$routing.generate('get_crypto_info', {symbol}));

            commit('setCrypto', {symbol, crypto: response.data});
        },
    },
};

export default storage;
