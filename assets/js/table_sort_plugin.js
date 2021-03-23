import moment from 'moment';
import {GENERAL} from './utils/constants';

const tableSortPlugin = {
    install(Vue, options) {
        Vue.prototype.$sortCompare = function(fields) {
            return function(a, b, key) {
                switch (fields[key].type) {
                    case 'date':
                        return Vue.prototype.$dateCompare(a[key], b[key]);
                    case 'string':
                        return a[key].localeCompare(b[key]);
                    case 'numeric':
                        return Vue.prototype.$numericCompare(a[key], b[key]);
                }
            };
        },
        Vue.prototype.$numericCompare = function(a, b) {
            a = parseFloat(a);
            b = parseFloat(b);

            return a < b ? -1 : (a > b ? 1 : 0);
        },
        Vue.prototype.$dateCompare = function(a, b) {
            a = moment(a, GENERAL.dateTimeFormat).unix();
            b = moment(b, GENERAL.dateTimeFormat).unix();

            return Vue.prototype.$numericCompare(a, b);
        };
    },
};

export default tableSortPlugin;
