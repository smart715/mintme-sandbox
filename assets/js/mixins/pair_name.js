import {MINTME, WEB} from '../utils/constants';

const pair = (baseSymbol, quoteSymbol) => {
    return MINTME.symbol === baseSymbol || WEB.symbol === baseSymbol
        ? quoteSymbol
        : `${quoteSymbol}/${baseSymbol}`;
};

export default {
    methods: {
        pairNameFunc: pair,
    },
};
