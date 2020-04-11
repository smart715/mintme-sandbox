import moment from 'moment';
import {GENERAL} from './utils/constants';

const tableSortPlugin = {
    install(Vue, options) {
<<<<<<< HEAD
        Vue.prototype.$sortCompare = function (fields){
            return function (a, b, key) {
                switch (fields[key].type) {
                    case 'date':
                        return Vue.prototype.$dateCompare(a[key], b[key]);
                    case 'string':
                        return a[key].localeCompare(b[key]);
                    case 'numeric':
                        return Vue.prototype.$numericCompare(a[key], b[key]);
                }
=======
        Vue.prototype.$sortCompare = function(a, b, key) {
            switch (this.fields[key].type) {
                case 'date':
                    return Vue.prototype.$dateCompare(a[key], b[key]);
                case 'string':
                    return a[key].localeCompare(b[key]);
                case 'numeric':
                    return Vue.prototype.$numericCompare(a[key], b[key]);
>>>>>>> 13ef851d8180931ec7c7a6880a193d3525e6b720
            }
        },
        Vue.prototype.$numericCompare = function(a, b) {
            a = parseFloat(a);
            b = parseFloat(b);

            return a < b ? -1 : (a > b ? 1 : 0);
        },
        Vue.prototype.$dateCompare = function(a, b) {
            a = moment(a, GENERAL.dateFormat).unix();
            b = moment(b, GENERAL.dateFormat).unix();

            return Vue.prototype.$numericCompare(a, b);
        };
    },
};

export default tableSortPlugin;
