import Decimal from 'decimal.js';

const storage = {
    namespaced: true,
    state: {
        serviceUnavailable: false,
        quoteBalance: 0,
        quoteBonusBalance: 0,
        quoteFullBalance: 0,
        baseBalance: 0,
        useSellMarketPrice: false,
        useBuyMarketPrice: false,
        sellPriceInput: 0,
        sellAmountInput: 0,
        sellTotalPriceInput: 0,
        buyPriceInput: 0,
        buyAmountInput: 0,
        buyTotalPriceInput: 0,
        subtractQuoteBalanceFromBuyAmount: false,
        takerFee: 0,
        balances: null,
        sellPriceManuallyEdited: false,
        buyPriceManuallyEdited: false,
        hasQuoteRelation: false,
        inputFlags: [],
    },
    getters: {
        isServiceUnavailable: function(state) {
            return state.serviceUnavailable;
        },
        hasQuoteRelation: function(state) {
            return state.hasQuoteRelation;
        },
        getQuoteBalance: function(state) {
            return state.quoteBalance;
        },
        getQuoteBonusBalance: function(state) {
            return state.quoteBonusBalance;
        },
        getQuoteFullBalance: function(state) {
            return state.quoteFullBalance;
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
        getSellTotalPriceInput: function(state) {
            return state.sellTotalPriceInput;
        },
        getBuyPriceInput: function(state) {
            return state.buyPriceInput;
        },
        getBuyAmountInput: function(state) {
            return state.buyAmountInput;
        },
        getBuyTotalPriceInput: function(state) {
            return state.buyTotalPriceInput;
        },
        getBalances: function(state) {
            return state.balances;
        },
        getSellPriceManuallyEdited: function(state) {
            return state.sellPriceManuallyEdited;
        },
        getBuyPriceManuallyEdited: function(state) {
            return state.buyPriceManuallyEdited;
        },
        getInputFlags: function(state) {
            return state.inputFlags;
        },
    },
    mutations: {
        setServiceUnavailable: function(state, n) {
            state.serviceUnavailable = n;
        },
        setHasQuoteRelation: function(state, n) {
            state.hasQuoteRelation = n;
        },
        setQuoteBalance: function(state, n) {
            state.quoteBalance = n;

            if (state.subtractQuoteBalanceFromBuyAmount) {
                const amount = (state.buyAmountInput - state.quoteBalance) / (1 - state.takerFee);

                state.buyAmountInput = new Decimal(amount)
                    .toDP(this.market.quote.subunit, Decimal.ROUND_CEIL).toNumber();
                state.subtractQuoteBalanceFromBuyAmount = false;
            }
        },
        setQuoteBonusBalance: function(state, n) {
            state.quoteBonusBalance = n;
        },
        setQuoteFullBalance: function(state, n) {
            state.quoteFullBalance = n;
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
        setSellTotalPriceInput: function(state, n) {
            state.sellTotalPriceInput = n;
        },
        setBuyPriceInput: function(state, n) {
            state.buyPriceInput = n;
        },
        setBuyAmountInput: function(state, n) {
            state.buyAmountInput = n;
        },
        setBuyTotalPriceInput: function(state, n) {
            state.buyTotalPriceInput = n;
        },
        setSubtractQuoteBalanceFromBuyAmount: function(state, n) {
            state.subtractQuoteBalanceFromBuyAmount = n;
        },
        setTakerFee: function(state, n) {
            state.takerFee = n;
        },
        setBalances: function(state, n) {
            state.balances = n;
        },
        setBalance: function(state, balance) {
            const currentBalance = state.balances[balance.fullname];

            if (!currentBalance) {
                return;
            }

            state.balances[balance.fullname] = {...currentBalance, ...balance};
        },
        setSellPriceManuallyEdited: function(state, n) {
            state.sellPriceManuallyEdited = n;
        },
        setSellAmountManuallyEdited: function(state, n) {
            state.sellAmountManuallyEdited = n;
        },
        setSellTotalPriceManuallyEdited: function(state, n) {
            state.sellTotalPriceManuallyEdited = n;
        },
        setBuyPriceManuallyEdited: function(state, n) {
            state.buyPriceManuallyEdited = n;
        },
        setBuyAmountManuallyEdited: function(state, n) {
            state.buyAmountManuallyEdited = n;
        },
        setBuyTotalPriceManuallyEdited: function(state, n) {
            state.buyTotalPriceManuallyEdited = n;
        },
        setInputFlags: function(state, n) {
            state.inputFlags = n;
        },
        removeInputFlag: function(state, n) {
            const newFlags = state.inputFlags.filter((flag) => flag !== n);
            state.inputFlags = newFlags;
        },
    },
};

export default storage;
