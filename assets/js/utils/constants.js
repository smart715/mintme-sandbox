import {helpers} from 'vuelidate/lib/validators';

// validation for address
export const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);
export const zipCodeContain = helpers.regex('zipCode', /^[a-zA-Z0-9-\s]+$/u);
export const tokenValidFirstChars = helpers.regex('firstChars', /^[-\s]+/u);
export const tokenValidLastChars = helpers.regex('lastChars', /[-\s]+$/u);
export const tokenNoSpaceBetweenDashes = helpers.regex('spaceBetweenDashes', /-+\s+-+/u);
export const tokenNameValidChars = helpers.regex('validChars', /^[-\sA-Za-z0-9]+$/u);

export const HTTP_OK = 200;
export const HTTP_ACCEPTED = 202;
export const HTTP_NO_CONTENT = 204;

export const GENERAL = {
    precision: 8,
    dateFormat: 'DD.MM.YYYY hh:mm:ss',
};

export const webSymbol = 'WEB';
export const btcSymbol = 'BTC';
export const tokSymbol = 'TOK';

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
};

// tabIndex: tab
export const tokenTabs = {
    0: 'intro',
    1: 'trade',
    2: 'donation',
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

export const BTC = {
    symbol: 'BTC',
    subunit: 8,
};

export const MINTME = {
    symbol: 'MINTME',
    subunit: 4,
};
