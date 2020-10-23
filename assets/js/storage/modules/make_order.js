import Decimal from 'decimal.js';

const storage = {
    namespaced: true,
    state: {
        quoteBalance: 0,
        baseBalance: 0,
        useSellMarketPrice: false,
        useBuyMarketPrice: false,
        sellPriceInput: 0,
        sellAmountInput: 0,
        buyPriceInput: 0,
        buyAmountInput: 0,
        subtractQuoteBalanceFromBuyAmount: false,
        takerFee: 0,
    },
    getters: {
        getQuoteBalance: function(state) {
            return state.quoteBalance;
        },
        getBaseBalance: function(state) {
            return state.baseBalance;
        },
        getUseSellMarketPrice: function(state) {
            return state.useSellMarketPrice;
        },
        getUseBuyMarketPrice: function(state) {
            return state.useBuyMarketPrice;
        },
        getSellPriceInput: function(state) {
            return state.sellPriceInput;
        },
        getSellAmountInput: function(state) {
            return state.sellAmountInput;
        },
        getBuyPriceInput: function(state) {
            return state.buyPriceInput;
        },
        getBuyAmountInput: function(state) {
            return state.buyAmountInput;
        },
    },
    mutations: {
        setQuoteBalance: function(state, n) {
            state.quoteBalance = n;

            if (state.subtractQuoteBalanceFromBuyAmount) {
                state.buyAmountInput = (new Decimal(state.buyAmountInput)
                    .toDP(this.market.quote.subunit, Decimal.ROUND_CEIL).toNumber() - state.quoteBalance)
                    / (1 - state.takerFee);
                state.subtractQuoteBalanceFromBuyAmount = false;
            }
        },
        setBaseBalance: function(state, n) {
            state.baseBalance = n;
        },
        setUseSellMarketPrice: function(state, n) {
            state.useSellMarketPrice = n;
        },
        setUseBuyMarketPrice: function(state, n) {
            state.useBuyMarketPrice = n;
        },
        setSellPriceInput: function(state, n) {
            state.sellPriceInput = n;
        },
        setSellAmountInput: function(state, n) {
            state.sellAmountInput = n;
        },
        setBuyPriceInput: function(state, n) {
            state.buyPriceInput = n;
        },
        setBuyAmountInput: function(state, n) {
            state.buyAmountInput = n;
        },
        setSubtractQuoteBalanceFromBuyAmount: function(state, n) {
          state.subtractQuoteBalanceFromBuyAmount = n;
        },
        setTakerFee: function(state, n) {
          state.takerFee = n;
        },
    },
};

export default storage;
