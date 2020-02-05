import {MINTME, WEB} from '../../utils/constants';

let pair = (baseSymbol, quoteSymbol) => {
  return MINTME.symbol === baseSymbol || WEB.symbol === baseSymbol
    ? `${quoteSymbol}`
    : `${baseSymbol}/${quoteSymbol}`;
};

export default {
    methods: {
        pairNameFunc: function(baseSymbol, quoteSymbol) {
            return pair(baseSymbol, quoteSymbol);
        },
    },
};
