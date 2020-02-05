import {BTC, MINTME} from '../../utils/constants';

let pair = (baseSymbol, quoteSymbol) => {
  return BTC.symbol === baseSymbol && MINTME.symbol === quoteSymbol ?
    `${baseSymbol}/${quoteSymbol}` : `${quoteSymbol}`;
};

export default {
    methods: {
        pairNameFunc: function(baseSymbol, quoteSymbol) {
            return pair(baseSymbol, quoteSymbol);
        },
    },
};
