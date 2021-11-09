let rebranding = (val) => {
    if (!val) {
        return val;
    }

    // Rebrand only Cryptos, not token names
    if ('object' === typeof val && val.hasOwnProperty('symbol')) {
        if (!cryptoSymbols.includes(val.symbol)) {
            return val.symbol;
        } else {
            val = val.symbol;
        }
    }


    const brandDict = [
        {regexp: /(Webchain)/g, replacer: 'MintMe Coin'},
        {regexp: /(webchain)/g, replacer: 'mintMe Coin'},
        {regexp: /(WEB)/g, replacer: 'MINTME'},
        {regexp: /(web)/g, replacer: 'MINTME'},
    ];
    brandDict.forEach((item) => {
        if (typeof val !== 'string') {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

import {cryptoSymbols} from '../../utils/constants';

export default {
    filters: {
        rebranding: function(val) {
            return rebranding(val);
        },
    },
    methods: {
        rebrandingFunc: function(val) {
            return rebranding(val);
        },
    },
};
