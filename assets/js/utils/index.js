import Decimal from 'decimal.js/decimal.js';
import * as Constants from './constants';
import Interval from './interval';
import EchartTheme from './echart-theme';
import {TEXT_HASHTAG} from './regex';

/**
 * Checks that given url is valid
 * @param {string} url
 * @return {boolean} whether is valid or not
 */
function isValidUrl(url) {
    const regex = new RegExp(
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
 * Checks that given telegram url is valid
 * @param {string} url
 * @return {boolean} whether is valid or not
 */
function isValidTelegramUrl(url) {
    const regex = new RegExp(
        '^https:\\/\\/(?:t|telegram)\\.(?:me|dog)\\/(joinchat\\/|\\+)?([\\w-]+)$'
    );

    return regex.test(url);
}
/**
 * Flats json response
 * @param {obj} obj
 * @param {obj} res
 * @param {string} extraKey
 * @return {obj} flatten object
 */
function flattenJSON(obj = {}, res = {}, extraKey = '') {
    for (const key in obj) {
        if ('object' !== typeof obj[key]) {
            res[extraKey + key] = obj[key];
        } else {
            flattenJSON(obj[key], res, `${extraKey}${key}.`);
        }
    }
    return res;
}
/**
 * Checks that given discord url is valid
 * @param {string} url
 * @return {boolean} whether is valid or not
 */
function isValidDiscordUrl(url) {
    const regex = new RegExp('^https:\/\/(discord\.gg|(discordapp|discord)\.com\/invite)\/([-\\w]{1,})$');

    return regex.test(url);
}

/**
 * @param {object|Array} object
 * @return {Array}
 */
function deepFlatten(object) {
    if ('object' === typeof object) {
        object = Array.from(Object.keys(object), (k) => object[k]);
    }

    const flat = [];

    object.forEach((item) => {
        if (Array.isArray(item) || 'object' === typeof item) {
            flat.push(...deepFlatten(item));
        } else {
            flat.push(item);
        }
    });

    return flat;
}

/**
 * @param {string|int|float} val
 * @param {int} precision
 * @param {boolean} fixedPoint
 * @return {string}
 */
function toMoney(val, precision = Constants.GENERAL.precision, fixedPoint = true) {
    Decimal.set({rounding: Decimal.ROUND_DOWN, toExpNeg: -20});

    val = new Decimal(val);
    precision = val.lessThan(1 / Math.pow(10, precision)) ? 0 : precision;
    val = val.toDP(precision);

    return fixedPoint
        ? val.toString()
        : val;
}

/**
 * @param {string|int|float} val
 * @param {int} precision
 * @return {string}
 */
function toMoneyWithTrailingZeroes(val, precision = Constants.GENERAL.precision) {
    return new Decimal(val).toFixed(precision);
}

/**
 * @param {string} str
 * @return {string}
 */
function formatMoney(str) {
    str = str ? str.toString() : '';
    str = str.split(/ (.+)/);
    const additional = str[1] || '';
    str = str[0];
    const regx = /(\d{1,3})(\d{3}(?:,|$))/;
    let currStr;

    do {
        currStr = (currStr || str.split(`.`)[0]).replace( regx, `$1,$2`);
    } while (currStr.match(regx));

    const res = ( str.split(`.`)[1] ) ?
        currStr.concat(`.`, str.split(`.`)[1]) :
        currStr;

    return `${res.replace(/,/g, ' ')} ${additional}`.trim();
}

/**
 * Get viabtc server offset
 * @return {Number}
 */
function getUserOffset() {
    const offset = document.querySelector('meta[name="X-USER-OFFSET"]');

    if (!offset) {
        return 0;
    }

    return parseInt(offset.getAttribute('content'));
}

/**
 * @return {string}
 */
function getBreakPoint() {
    return window.getComputedStyle(document.body)
        .getPropertyValue('content').replace(/"/g, '');
}

/**
 * @param {string} amount
 * @return {string}
 */
function removeSpaces(amount) {
    return amount.replace(/\s+/g, '');
}

/**
 * @param {string} link
 * @return {Promise}
 */
function openPopup(link) {
    return new Promise((resolve) => {
        const popup = window.open(link, 'popup', 'width=600,height=600');

        const interval = setInterval(() => {
            if (popup.closed) {
                clearInterval(interval);
                resolve();
            }
        }, 1000);
    });
}

/**
 * @param {string} link
 * @return {undefined}
 */
function openNewTab(link) {
    const a = document.createElement('a');
    a.href = link;
    a.target = '_blank';
    a.click();
}

/**
 * @param {array} arr
 * @param {string} prop
 * @param {boolean} excludeEmpty
 * @return {boolean}
 */
function assertUniquePropertyValuesInObjectArray(arr, prop, excludeEmpty = true) {
    const values = {};

    return arr.every((item) => {
        if (excludeEmpty && '' === item[prop]) {
            return true;
        }

        if (values[item[prop]]) {
            return false;
        }

        values[item[prop]] = true;
        return true;
    });
}

/**
 * @param {string} cryptoSymbol
 * @return {string}
 */
function getTokenOnBlockchainMsg(cryptoSymbol) {
    return Constants.MINTME.symbol === cryptoSymbol
        ? 'trading.exist_on_blockchain_guide.mintme'
        : 'trading.exist_on_blockchain_guide.others';
}


/**
 * @return {string}
 */
function getScreenMediaSize() {
    const width = window.innerWidth;

    if (345 > width) {
        return Constants.ScreenMediaSize.XXS;
    }

    if (576 > width) {
        return Constants.ScreenMediaSize.XS;
    }
    if (768 > width) {
        return Constants.ScreenMediaSize.SM;
    }
    if (992 > width) {
        return Constants.ScreenMediaSize.MD;
    }
    if (1200 > width) {
        return Constants.ScreenMediaSize.LG;
    }
    if (1400 > width) {
        return Constants.ScreenMediaSize.XL;
    }

    return Constants.ScreenMediaSize.XXL;
}

/**
 * @param {string} str
 * @return {string}
 */
function rtrimZeros(str) {
    return str.replace(/\.?0+$/, '');
}

/**
 * @param {string|number} num
 * @return {string}
 */
function toIntegerWithSpaces(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}


/**
 * @param {Boolean} isCrypto
 * @param {Boolean} isUserToken
 * @param {String} symbol
 * @param {Boolean} isDeployed
 * @param {[String, Object]} image
 * @return {String}
 */
function getAvatarImg(isCrypto, isUserToken, symbol, isDeployed, image) {
    if ([Constants.WEB.symbol, Constants.MINTME.symbol].includes(symbol)) {
        return Constants.TOKEN_MINTME_ICON_URL;
    }

    if (isCrypto) {
        return require(`../../img/${getTokenIconBySymbol(symbol)}`);
    }
    if (isUserToken) {
        return getUserTokenImg(image);
    }

    return getNotUserTokenImg(isDeployed, symbol);
}

/**
 * @param {[String, Object]} image
 * @return {String}
 */
function getUserTokenImg(image) {
    if ('string' === typeof image || image instanceof String) {
        return image;
    }

    return (image && Constants.TOKEN_DEFAULT_ICON_URL !== image.url)
        ? image.url
        : Constants.TOKEN_DEFAULT_ICON_URL;
}
/**
 * @param {Boolean} isDeployed
 * @param {String} symbol
 * @return {String}
 */
function getNotUserTokenImg(isDeployed, symbol) {
    return isDeployed
        ? require(`../../img/${getTokenIconBySymbol(symbol)}`)
        : '';
}

/**
 * @param {String} symbol
 * @return {String}
 */
function getTokenIconBySymbol(symbol) {
    return symbol ? `${symbol}.svg` : Constants.TOKEN_DEFAULT_ICON_NAME;
}

/**
 * @param {String} src
 * @param {String} classes
 * @param {String} symbol
 * @param {Boolean} isDark
 * @return {String}
 */
function coinAvatarHtmlTemplate({
    src,
    symbol,
    classes='coin-avatar-sm',
    isDark=false,
}) {
    const isMintme = Constants.TOKEN_MINTME_ICON_URL === src;

    return `
        <span class="coin-avatar">
            <img
                alt="avatar"
                src="${src}"
                class="
                    rounded-circle ${classes} ${isMintme ? 'coin-avatar-mintme' : ''}
                    ${isDark ? 'dark-coin-avatar' : ''}
                "
            />
            ${symbol ? symbol : ''}
        </span>
    `;
}

/**
 * @param {[String, Object]} image
 * @param {String} symbol
 * @param {Boolean} isCrypto
 * @param {Boolean} isDeployed
 * @param {Boolean} isUserToken
 * @param {String} width
 * @param {String} classes
 * @param {String} withSymbol
 * @return {String}
 */
function generateCoinAvatarHtml({
    image,
    symbol,
    isDark,
    classes,
    isCrypto=false,
    isDeployed=false,
    isUserToken=false,
    withSymbol=true,
}) {
    const imgSrc = getAvatarImg(isCrypto, isUserToken, symbol, isDeployed, image);
    const appendSymbol = withSymbol && symbol;

    return coinAvatarHtmlTemplate({
        src: imgSrc,
        classes: classes,
        isDark: isDark,
        ...(appendSymbol),
    });
}

/**
 * @param {String} withSymbol
 * @return {String}
 */
function generateMintmeAvatarHtml(withSymbol=true) {
    return coinAvatarHtmlTemplate({
        src: Constants.TOKEN_MINTME_ICON_URL,
        ...(withSymbol && {symbol: Constants.MINTME.symbol}),
    });
}

/**
 * @param {Array} topHolders
 * @param {string} nickname
 * @return {String|null}
 */
function getRankMedalSrcByNickname(topHolders, nickname) {
    const topHolder = topHolders.find((topHolder) => topHolder.user.profile.nickname === nickname);

    return topHolder && Constants.RANK_MEDALS[topHolder.rank]
        ? getRankMedalSrcByRank(topHolder.rank)
        : null;
}

/**
 * @param {Number} rank
 * @return {String}
 */
function getRankMedalSrcByRank(rank) {
    return require(`../../img/awards/${Constants.RANK_MEDALS[rank]}`);
}


/**
 * @param {Number} rank
 * @return {String}
 */
function getRankWreathSrcByRank(rank) {
    return require(`../../img/awards/${Constants.RANK_WREATHS[rank]}`);
}

/**
 * @param {string} symbol
 * @param {boolean} isAvatar
 * @return {String}
 */
function getCoinAvatarAssetName(symbol, isAvatar = false) {
    symbol = Constants.MINTME.symbol == symbol ? Constants.webSymbol : symbol;

    return isAvatar
        ? `${symbol}_avatar.svg`
        : `${symbol}.svg`;
}

/**
 * @param {string} text
 * @param {string} urlPattern
 * @return {String}
 */
function addHtmlHashtagsToText(text, urlPattern) {
    return text?.replace(
        TEXT_HASHTAG,
        `<a href="${urlPattern}" class="text-primary">#$1</a>`
    );
}

/**
 * @param {String} value
 * @return {String}
 */
function getPriceAbbreviation(value) {
    const strVal = value.toString();

    if ('0' === strVal) {
        return '0';
    }

    if (!value) {
        return '';
    }

    if (!strVal.includes('.') && !strVal.includes('e')) {
        return value;
    }

    if (isScientificNotation(value)) {
        value = scientificToNormal(value);
    }

    const values = strVal.split('.');

    return 2 <= values.length
        ?`${values[0]}.${values[1].slice(0, 1)}...${values[1].slice(-4)}`
        : `${strVal.slice(0, 3)}...${strVal.slice(-4)}`;
}

/**
 * @param {String|number} value
 * @return {boolean}
 */
function isScientificNotation(value) {
    return /\d+\.?\d*e[+-]?\d+/i.test(value);
}

/**
 * @param {String|number} scientificNotation
 * @return {string}
 */
function scientificToNormal(scientificNotation) {
    const [mantissa, exponent] = scientificNotation.toString().split('e');
    const normalizedNumber = (+mantissa) * Math.pow(10, +exponent);

    return normalizedNumber.toFixed(Math.abs(exponent));
}

export {
    isValidUrl,
    isValidTelegramUrl,
    isValidDiscordUrl,
    deepFlatten,
    flattenJSON,
    toMoney,
    toMoneyWithTrailingZeroes,
    formatMoney,
    Constants,
    EchartTheme,
    Interval,
    getUserOffset,
    getBreakPoint,
    removeSpaces,
    openPopup,
    openNewTab,
    assertUniquePropertyValuesInObjectArray,
    getTokenOnBlockchainMsg,
    getScreenMediaSize,
    rtrimZeros,
    toIntegerWithSpaces,
    getTokenIconBySymbol,
    getUserTokenImg,
    generateCoinAvatarHtml,
    coinAvatarHtmlTemplate,
    generateMintmeAvatarHtml,
    getRankMedalSrcByRank,
    getRankWreathSrcByRank,
    getRankMedalSrcByNickname,
    getCoinAvatarAssetName,
    addHtmlHashtagsToText,
    getPriceAbbreviation,
};
