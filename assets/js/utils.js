import Decimal from 'decimal.js';
import {GENERAL} from './utils/constants';

/**
 * Checks that given url is valid
 * @param {string} url
 * @return {boolean} whether is valid or not
 */
export function isValidUrl(url) {
    let regex = new RegExp(
        '^' +
          // protocol identifier
          '(?:(?:https?|ftp)://)' +
          // user:pass authentication
          '(?:\\S+(?::\\S*)?@)?' +
          '(?:' +
            // IP address exclusion
            // private & local networks
            '(?!(?:10|127)(?:\\.\\d{1,3}){3})' +
            '(?!(?:169\\.254|192\\.168)(?:\\.\\d{1,3}){2})' +
            '(?!172\\.(?:1[6-9]|2\\d|3[0-1])(?:\\.\\d{1,3}){2})' +
            // IP address dotted notation octets
            // excludes loopback network 0.0.0.0
            // excludes reserved space >= 224.0.0.0
            // excludes network & broacast addresses
            // (first & last IP address of each class)
            '(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])' +
            '(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}' +
            '(?:\\.(?:[1-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))' +
          '|' +
            // host name
            '(?:(?:[a-z\\u00a1-\\uffff0-9]-*)*[a-z\\u00a1-\\uffff0-9]+)' +
            // domain name
            '(?:\\.(?:[a-z\\u00a1-\\uffff0-9]-*)*[a-z\\u00a1-\\uffff0-9]+)*' +
            // TLD identifier
            '(?:\\.(?:[a-z\\u00a1-\\uffff]{2,}))' +
            // TLD may end with dot
            '\\.?' +
          ')' +
          // port number
          '(?::\\d{2,5})?' +
          // resource path
          '(?:[/?#]\\S*)?' +
        '$', 'i'
      );

      return regex.test(url);
}

/**
 * @param {object|Array} object
 * @return {Array}
 */
export function deepFlatten(object) {
    if (typeof object === 'object') {
        object = Array.from(Object.keys(object), (k) => object[k]);
    }

    const flat = [];

    object.forEach((item) => {
        if (Array.isArray(item) || typeof item === 'object') {
            flat.push(...deepFlatten(item));
        } else {
            flat.push(item);
        }
    });

    return flat;
}

/**
 * @param {string|int|float} val
 * @return {string}
 */
export function toMoney(val) {
    return new Decimal(val).toFixed(GENERAL.precision);
}

/**
 * @param {string} address
 * @return {boolean} whether is valid or not
 */
export function isValidEmail(address) {
    let regex = /^\S+@\S+$/;
    return regex.test(address);
}
