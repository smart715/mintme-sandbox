const tradingSortPlugin = {
    install(Vue, options) {
        Vue.sortCompare = function(a, b, key) {
            let pair = false;
            this.marketsOnTop.forEach((market)=> {
                let currency = this.rebrandingFunc(market.currency);
                let token = this.rebrandingFunc(market.token);

                if (b.pair === currency + '/' + token || a.pair === currency + '/' + token) {
                    pair = true;
                }
            });
            let numeric = key !== this.fields.pair.key;

            if (numeric || (typeof a[key] === 'number' && typeof b[key] === 'number')) {
                let first = parseFloat(a[key]);
                let second = parseFloat(b[key]);

                return pair ? 0 : (first < second ? -1 : ( first > second ? 1 : 0));
            }

            // If the value is not numeric, currently only pair column
            // b and a are reversed so that 'pair' column is ordered A-Z on first click (DESC, would be Z-A)
            return pair ? 0 : b[key].localeCompare(a[key]);
        };
    },
};

export default tradingSortPlugin;
