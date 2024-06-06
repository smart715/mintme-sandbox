const rebranding = (val) => {
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
        if ('string' !== typeof val) {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

const reverseRebranding = (val) => {
    if (!val) {
        return val;
    }

    // Reverse rebrand only Cryptos, not token names
    if ('object' === typeof val && val.hasOwnProperty('symbol')) {
        val = val.symbol;
    }

    const brandDict = [
        {regexp: /(MintMe Coin)/g, replacer: 'Webchain'},
        {regexp: /(mintMe Coin)/g, replacer: 'webchain'},
        {regexp: /(MINTME)/g, replacer: 'WEB'},
        {regexp: /(mintme)/g, replacer: 'WEB'},
    ];

    brandDict.forEach((item) => {
        if ('string' !== typeof val) {
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
        reverseRebranding: function(val) {
            return reverseRebranding(val);
        },
    },
    methods: {
        rebrandingFunc: function(val) {
            return rebranding(val);
        },
        reverseRebrandingFunc: function(val) {
            return reverseRebranding(val);
        },
    },
};
