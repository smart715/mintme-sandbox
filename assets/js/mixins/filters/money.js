import {formatMoney, toMoney} from '../../utils';

export default {
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
    },
};
