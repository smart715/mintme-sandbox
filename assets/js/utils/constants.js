import {helpers, required} from 'vuelidate/lib/validators';

export const requiredBBCText = (val) => required(
    val.replace(/\[\s*\/?\s*(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)\s*\]/g, '').trim()
);

// validation for address
export const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);
export const addressFirstSymbol = {
    'WEB': (address) => address.startsWith('0x'),
    'BTC': () => true,
};
export const zipCodeContain = helpers.regex('zipCode', /^[a-zA-Z0-9-\s]+$/u);
export const tokenValidFirstChars = helpers.regex('firstChars', /^[\s]+/u);
export const tokenValidLastChars = helpers.regex('lastChars', /[\s]+$/u);
export const tokenNoSpaceBetweenDashes = helpers.regex('spaceBetweenDashes', /-+\s+-+/u);
export const tokenNameValidChars = helpers.regex('validChars', /^[\sA-Za-z0-9]+$/u);
export const twoFACode = helpers.regex('numberInput', /^\d{6}$|^[A-Za-z\d]{12}$/);
export const phoneVerificationCode = helpers.regex('code', /^\d{6}$/);
export const nickname = helpers.regex('nickname', /^[A-Za-z\d]+$/u);
export const names = helpers.regex('names', /^[A-Za-z]+[A-Za-z\s'‘’`´-]*$/u);
export const allNames = helpers.regex('allNames', /^[A-Za-z\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff\uff66-\uff9f-\s'‘’`´-]+$/u);
export const tweetLink = helpers.regex('tweetLink', /^(https?:\/\/)?(www\.)?twitter\.com\/[\S]+\/status\/[\d]+$/u);
export const facebookPostLink = helpers.regex('facebookPostLink', /^(https?:\/\/)?(www\.)?facebook\.com\/[\S]+\/posts\/[\d]+$/u);

export const FORBIDDEN_WORDS = ['token', 'coin'];
export const HTTP_OK = 200;
export const HTTP_ACCEPTED = 202;
export const HTTP_NO_CONTENT = 204;
export const HTTP_BAD_REQUEST = 400;
export const HTTP_UNAUTHORIZED = 401;
export const HTTP_NOT_FOUND = 404;

export const AIRDROP_CREATED = 'airdrop_created';
export const AIRDROP_DELETED = 'airdrop_deleted';
export const TOKEN_NAME_CHANGED = 'token_name_changed';

export const GENERAL = {
    precision: 8,
    dateTimeFormat: 'DD.MM.YYYY HH:mm:ss',
    dateTimeFormatPicker: 'MM.DD.YYYY HH:mm',
    dateFormat: 'MMM D, YYYY',
    timeFormat: 'HH:mm',
};

export const webSymbol = 'WEB';
export const btcSymbol = 'BTC';
export const ethSymbol = 'ETH';
export const tokSymbol = 'TOK';
export const usdcSymbol = 'USDC';
export const tokEthSymbol = 'TOKETH';
export const webBtcSymbol = 'WEBBTC';
export const webEthSymbol = 'WEBETH';
export const webUsdcSymbol = 'WEBUSDC';
export const usdSign = '$';

export const cryptoSymbols = [
    webSymbol,
    btcSymbol,
];

export const tokenDeploymentStatus = {notDeployed: 'not-deployed', pending: 'pending', deployed: 'deployed'};
export const addressLength = {
    WEB: {
        min: 42,
        max: 42,
    },
    BTC: {
        min: 25,
        max: 42,
    },
    ETH: {
        min: 42,
        max: 42,
    },
};

export const WSAPI = {
    order: {
        status: {
            PUT: 1,
            UPDATE: 2,
            FINISH: 3,
        },
        type: {
            SELL: 1,
            BUY: 2,
            DONATION: 3,
        },
    },
};

export const USD = {
    symbol: 'USD',
    subunit: 4,
};

export const WEB = {
    symbol: 'WEB',
    subunit: 4,
    digits: 8,
};

export const TOK = {
    symbol: 'TOK',
    subunit: 4,
};

export const BTC = {
    symbol: 'BTC',
    subunit: 8,
    digits: 4,
};

export const ETH = {
    symbol: 'ETH',
    subunit: 8,
    digits: 4,
};

export const USDC = {
    symbol: 'USDC',
    subunit: 18,
};

export const MINTME = {
    symbol: 'MINTME',
    subunit: 4,
};

export const sanitizeOptions = {
    allowedTags: ['a', 'img', 'ul', 'li', 'ol', 'h1', 'h2', 'h3', 'h4', 'h5', 'h5', 'h6', 'url', 'span', 's', 'p', 'iframe', 'div'],
    allowedAttributes: {
        'iframe': ['height', 'width', 'allow', 'frameborder', 'src', 'allowfullscreen', 'class'],
        'div': ['class'],
        'ul': ['class'],
        'img': ['style', 'src'],
        'a': ['href', 'rel', 'target'],
        'span': ['style', 'class'],
    },
};

export const primaryColor = '0E3B58';

export const notificationTypes = {
    filled: 'filled',
    cancelled: 'cancelled',
    deposit: 'deposit',
    withdrawal: 'withdrawal',
    deployed: 'deployed',
    newPost: 'new_post',
    newInvestor: 'new_investor',
    tokenMarketingTips: 'token_marketing_tips',
};

export const tabs = {
    intro: 'intro',
    posts: 'posts',
    trade: 'trade',
    post: 'post',
    voting: 'voting',
    create_voting: 'create-voting',
    show_voting: 'show-voting',
};

export const tabsArr = Object.values(tabs);

export const descriptionLength = {
    min: 200,
    max: 10000,
};

export const digitsLimits = {
    WEB: WEB.digits,
    BTC: BTC.digits,
    ETH: ETH.digits,
};

export const currencyModes = {
    usd: {
        value: 'usd',
        text: 'USD',
    },
    crypto: {
        value: 'crypto',
        text: 'Crypto',
    },
};

export const currencies = {
    WEB,
    MINTME,
    BTC,
    TOK,
    USD,
    ETH,
    USDC,
};
