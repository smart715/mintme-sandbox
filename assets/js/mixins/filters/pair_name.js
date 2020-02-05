import {BTC, MINTME, WEB} from '../../utils/constants';

let pair = (baseSymbol, quoteSymbol) => {
  return BTC.symbol === baseSymbol && (MINTME.symbol === quoteSymbol || WEB.symbol === quoteSymbol) ?
    `${baseSymbol}/${quoteSymbol}` : `${quoteSymbol}`;
};

export default {
    methods: {
        pairNameFunc: function(baseSymbol, quoteSymbol) {
            return pair(baseSymbol, quoteSymbol);
        },
    },
};
