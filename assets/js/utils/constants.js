import {helpers} from 'vuelidate/lib/validators';

// validation for address
export const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);
export const tokenValidFirstChars = !helpers.regex('firstChars', /^[-\s]+/u);
export const tokenValidLastChars = !helpers.regex('lastChars', /[-\s]+$/u);
export const tokenNoSpaceBetweenDashes = !helpers.regex('spaceBetweenDashes', /-+\s+-+/u);

export const GENERAL = {
    precision: 8,
    dateFormat: 'DD.MM.YYYY hh:mm:ss',
};
export const webSymbol = 'web';
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
