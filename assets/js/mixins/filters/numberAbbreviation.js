import {
    NUMBER_ABBREVIATION_PRECISION,
    NUMBER_ABBREVIATION_UNIT,
} from '../../utils/constants';
import NumberAbbr from 'number-abbreviate';

const BILLION_NUMBER = 1000000000;
const MILLION_NUMBER = 1000000;
const THOUSAND_NUMBER = 1000;
const BILLION_LETTER = 'B';
const MILLION_LETTER = 'M';
const THOUSAND_LETTER = 'K';

const numberAbbrFunc = function(val) {
    const numberAbbr = new NumberAbbr(NUMBER_ABBREVIATION_UNIT);
    return 1000 > val ? val : numberAbbr.abbreviate(val, NUMBER_ABBREVIATION_PRECISION);
};

const toFixedFunc = function(value, decimals = 2) {
    return parseFloat(value).toFixed(decimals);
};

const numberTruncateWithLetterFunc = function(number) {
    if (BILLION_NUMBER <= number) {
        return (number / BILLION_NUMBER).toString().substring(0, 5) + BILLION_LETTER;
    }
    if (MILLION_NUMBER <= number) {
        return (number / MILLION_NUMBER).toString().substring(0, 5) + MILLION_LETTER;
    }
    if (THOUSAND_NUMBER <= number) {
        return (number / THOUSAND_NUMBER).toString().substring(0, 5) + THOUSAND_LETTER;
    }

    return parseFloat(number).toFixed(4);
};

export default {
    methods: {
        numberAbbrFunc: numberAbbrFunc,
        toFixedFunc: toFixedFunc,
        numberTruncateWithLetterFunc: numberTruncateWithLetterFunc,
    },
    filters: {
        numberAbbr: numberAbbrFunc,
        toFixed: toFixedFunc,
        numberTruncateWithLetter: numberTruncateWithLetterFunc,
    },
};
