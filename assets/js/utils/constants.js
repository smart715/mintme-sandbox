import {helpers} from 'vuelidate/lib/validators';

// validation for address
export const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);
export const addressFirstSymbol = {
    'WEB': (address) => address.startsWith('0x'),
    'BTC': () => true,
};
export const zipCodeContain = helpers.regex('zipCode', /^[a-zA-Z0-9-\s]+$/u);
export const tokenValidFirstChars = helpers.regex('firstChars', /^[-\s]+/u);
export const tokenValidLastChars = helpers.regex('lastChars', /[-\s]+$/u);
export const tokenNoSpaceBetweenDashes = helpers.regex('spaceBetweenDashes', /-+\s+-+/u);
export const tokenNameValidChars = helpers.regex('validChars', /^[-\sA-Za-z0-9]+$/u);

export const nickname = helpers.regex('nickname', /^[A-Za-z\d]+$/u);
export const names = helpers.regex('names', /^[A-Za-z]+[A-Za-z\s'‘’`´-]*$/u);
export const allNames = helpers.regex('allNames', /^[A-Za-z\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff\uff66-\uff9f-\s'‘’`´-]+$/u);

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
    dateFormat: 'DD.MM.YYYY HH:mm:ss',
};

export const webSymbol = 'WEB';
export const btcSymbol = 'BTC';
export const ethSymbol = 'ETH';
export const tokSymbol = 'TOK';
export const webBtcSymbol = 'WEBBTC';

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
};

export const TOK = {
    symbol: 'TOK',
    subunit: 4,
};

export const BTC = {
    symbol: 'BTC',
    subunit: 8,
};

export const ETH = {
    symbol: 'ETH',
    subunit: 18,
};

export const MINTME = {
    symbol: 'MINTME',
    subunit: 4,
};

export const sanitizeOptions = {
    allowedTags: ['a', 'img', 'ul', 'li', 'ol', 'h1', 'h2', 'h3', 'h4', 'h5', 'h5', 'h6', 'url', 'span', 's', 'p'],
    allowedAttributes: {
        'ul': ['class'],
        'img': ['style', 'src'],
        'a': ['href', 'rel', 'target'],
        'span': ['style', 'class'],
    },
};
