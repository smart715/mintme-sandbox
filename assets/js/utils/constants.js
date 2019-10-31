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

export const notAvailZipCodes = ['', 'AO', 'AG', 'AW', 'BS', 'BZ', 'BJ', 'BM', 'BO', 'BQ', 'BW', 'BF', 'BI', 'CM', 'CF', 'TD', 'KM', 'CD', 'CG', 'CK', 'CI', 'CW', 'DJ', 'DM', 'TL', 'GQ', 'ER', 'FJ', 'TF', 'GA', 'GM', 'GH', 'GD', 'GY', 'HM', 'HK', 'IE', 'KI', 'KP', 'LY', 'MO', 'MW', 'ML', 'MR', 'NA', 'NR', 'NL', 'NU', 'QA', 'RW', 'KN', 'ST', 'SC', 'SL', 'SX', 'SB', 'SR', 'SY', 'TG', 'TK', 'TO', 'TV', 'UG', 'AE', 'VU', 'YE', 'ZW'];

export const USD = {
    symbol: 'USD',
    subunit: 4,
};
