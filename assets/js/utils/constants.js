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
