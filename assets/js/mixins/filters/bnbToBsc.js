import {bnbSymbol, bscSymbol} from '../../utils/constants';

const bnbToBsc = (val) => {
    return val === bnbSymbol ? bscSymbol : val;
};

export default {
    filters: {
        bnbToBsc: function(val) {
            return bnbToBsc(val);
        },
    },
    methods: {
        bnbToBscFunc: function(val) {
            return bnbToBsc(val);
        },
    },
};
