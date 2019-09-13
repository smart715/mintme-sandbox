import {formatMoney, toMoney, formatFee} from '../../utils';

export default {
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
        formatMoney: function(val) {
            return formatMoney(val);
        },
        formatFee: function(val) {
            return formatFee(val);
        },
    },
};
