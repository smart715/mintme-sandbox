import {helpers, required} from 'vuelidate/lib/validators';

export const projectName = 'Mintme';
export const testSymbolAppend = '_TEST';
export const webSymbol = 'WEB';
export const btcSymbol = 'BTC';
export const ethSymbol = 'ETH';
export const croSymbol = 'CRO';
export const bnbSymbol = 'BNB';
export const bscSymbol = 'BSC';
export const tokSymbol = 'TOK';
export const usdSign = '$';
export const TOKEN_NAME_TRUNCATE_LENGTH = 15;
export const CONTENT_TRUNCATE_LENGTH = 100;
export const NICKNAME_TRUNCATE_LENGTH = 15;
export const MAX_NUMBER_1K = 1000;

export const cryptoSymbols = [
    webSymbol,
    btcSymbol,
];

export const requiredBBCText = (val) => required(
    val.replace(/\[\s*\/?\s*(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)\s*\]/g, '').trim()
);

export const requiredPostInput = (val) => required(
    val.replace(/<\/?[^>]+(>|$)/g, '').trim()
);
// validation for address
export const addressContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);
export const zipCodeContain = helpers.regex('zipCode', /^[a-zA-Z0-9-\s]+$/u);
export const tokenValidFirstChars = helpers.regex('firstChars', /^[\s]+/u);
export const tokenValidLastChars = helpers.regex('lastChars', /[\s]+$/u);
export const tokenNoSpaceBetweenDashes = helpers.regex('spaceBetweenDashes', /-+\s+-+/u);
export const tokenNameValidChars = helpers.regex('validChars', /^[\sA-Za-z0-9]+$/u);
export const twoFACode = helpers.regex('numberInput', /^\d{6}$|^[A-Za-z\d]{12}$/);
export const phoneVerificationCode = helpers.regex('code', /^\d{6}$/);
export const nickname = helpers.regex('nickname', /^[A-Za-z\d]+$/u);
export const email = helpers.regex('email', /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/);
export const emailLength = helpers.regex('email', /^[A-Za-z0-9._%+-]{1,64}@[A-Za-z0-9.-]{1,255}$/);
export const names = helpers.regex('names', /^[A-Za-z]+[A-Za-z\s'‘’`´-]*$/u);
export const allNames = helpers.regex('allNames', /^[\p{L}]+[\p{L}\s'‘’`´-]*$/u);
export const tweetLink = helpers.regex('tweetLink', /^(https?:\/\/)?(www\.)?twitter\.com\/[\S]+\/status\/[\d]+$/u);
export const facebookPostLink = helpers.regex(
    'facebookPostLink',
    /^(https?:\/\/)?(www\.)?facebook\.com\/[\S]+\/posts\/[A-Za-z0-9]+$/u
);
export const hex = helpers.regex('hex', /^#[0-9a-fA-F]{6}$/u);

export const FORBIDDEN_WORDS = ['token', 'coin'];
export const HTTP_OK = 200;
export const HTTP_CREATED = 201;
export const HTTP_ACCEPTED = 202;
export const HTTP_NO_CONTENT = 204;
export const HTTP_BAD_REQUEST = 400;
export const HTTP_UNAUTHORIZED = 401;
export const HTTP_NOT_FOUND = 404;
export const HTTP_LOCKED = 423;
export const TYPE_REWARD = 'reward';
export const TYPE_BOUNTY = 'bounty';
export const TOKEN_DEPLOYMENT = 'token_deployment';
export const TOKEN_CONNECTION = 'token_connection';
export const TOKEN_RELEASE_ADDRESS = 'token_release_address';
export const TOKEN_NEW_MARKET = 'token_new_market';
export const REWARD_PENDING = 'pending';
export const REWARD_NOT_COMPLETED = 'not_completed';
export const REWARD_COMPLETED = 'completed';
export const REWARD_REFUNDED = 'refunded';
export const REWARD_DELIVERED = 'delivered';
export const ADD_TYPE_REWARDS_MODAL = 'add';
export const EDIT_TYPE_REWARDS_MODAL = 'edit';
export const HTTP_ACCESS_DENIED = 403;
export const HTTP_INTERNAL_SERVER_ERROR = 500;

export const TYPE_BUY = 'Buy';
export const TYPE_SELL = 'Sell';

export const AIRDROP_CREATED = 'airdrop_created';
export const AIRDROP_DELETED = 'airdrop_deleted';
export const TOKEN_NAME_CHANGED = 'token_name_changed';
export const TOKEN_DEFAULT_ICON_URL = '/media/default_token.png';
export const TOKEN_DEFAULT_ICON_NAME = 'default_token_avatar.svg';
export const TOKEN_MINTME_ICON_URL = '/media/default_mintme.svg';
export const WEB_GREY_ICON_NAME = 'WEB_grey.svg';

export const GENERAL = {
    precision: 8,
    dateTimeFormat: 'HH:mm:ss D MMM, YYYY',
    dateTimeFormatPicker: 'DD-MM-YYYY HH:mm',
    dateFormat: 'D MMM, YYYY',
    dayMonthFormat: 'D MMM',
    timeFormat: 'HH:mm',
    date: 'MM-DD-YYYY',
    dateTimeFormatTable: 'HH:mm D MMM',
};

export const usdCustomPricePrecision = 18;
export const tokenDeploymentStatus = {
    deployed: 'deployed',
    pending: 'pending',
    notDeployed: 'not-deployed',
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
            TOKEN_TRADED: 8,
        },
    },
};

export const USD = {
    symbol: 'USD',
    icon: 'USD.png',
    subunit: 4,
};

export const WEB = {
    symbol: 'WEB',
    icon: 'WEB.svg',
    avatar: 'WEB_avatar.svg',
    subunit: 4,
    digits: 8,
};

export const logoWithText = {
    icon: 'logo.svg',
};

export const TOK = {
    symbol: 'TOK',
    icon: 'WEB.svg',
    avatar: 'WEB_avatar.svg',
    subunit: 4,
};

export const BTC = {
    symbol: 'BTC',
    icon: 'BTC.svg',
    avatar: 'BTC_avatar.svg',
    subunit: 8,
    digits: 4,
};

export const ETH = {
    symbol: 'ETH',
    icon: 'ETH.svg',
    avatar: 'ETH_avatar.svg',
    subunit: 8,
    digits: 4,
};

export const USDC = {
    symbol: 'USDC',
    icon: 'USDC.svg',
    avatar: 'USDC_avatar.svg',
    subunit: 6,
};

export const MINTME = {
    symbol: 'MINTME',
    icon: 'WEB.svg',
    avatar: 'WEB_avatar.svg',
    subunit: 4,
};

export const BNB = {
    symbol: 'BNB',
    icon: 'BNB.svg',
    avatar: 'BNB_avatar.svg',
    subunit: 8,
};

export const AVAX = {
    symbol: 'AVAX',
    icon: 'AVAX.svg',
    avatar: 'AVAX_avatar.svg',
    subunit: 8,
};

export const sanitizeOptions = {
    allowedTags: [
        'a', 'img', 'ul',
        'li', 'ol', 'h1',
        'h2', 'h3', 'h4',
        'h5', 'h5', 'h6',
        'url', 'span', 's',
        'p', 'iframe', 'div',
    ],
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
    transaction_delayed: 'transaction.delayed',
    deployed: 'deployed',
    newPost: 'new_post',
    newInvestor: 'new_investor',
    tokenMarketingTips: 'token_marketing_tips',
    reward_participant: 'reward.participant',
    reward_volunteer_new: 'reward.volunteer.new',
    reward_volunteer_accepted: 'reward.volunteer.accepted',
    reward_volunteer_completed: 'reward.volunteer.completed',
    reward_volunteer_rejected: 'reward.volunteer.rejected',
    reward_participant_rejected: 'reward.participant.rejected',
    reward_participant_delivered: 'reward.participant.delivered',
    reward_participant_refunded: 'reward.participant.refunded',
    reward_new: 'reward.new',
    reward_new_grouped: 'reward.new_grouped',
    bounty_new: 'bounty.new',
    bounty_new_grouped: 'bounty.new_grouped',
    market_created: 'market.created',
    new_buy_order: 'new_buy_order',
    broadcast: 'broadcast',
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

/* For dinamic changes with tabIndex */
export const tabsByNumber = {
    0: tabs.intro,
    1: tabs.posts,
    2: tabs.trade,
    3: tabs.post,
    4: tabs.voting,
    5: tabs.create_voting,
    6: tabs.show_voting,
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

export const mintmeUrlHost = 'mintme.com';
export const mintmeUrl = `https://www.${mintmeUrlHost}/`;

export const ScreenMediaSize = {
    XXS: -1,
    XS: 0,
    SM: 1,
    MD: 2,
    LG: 3,
    XL: 4,
    XXL: 5,
};

export const transactionType = {
    DEPOSIT: 'deposit',
    WITHDRAW: 'withdraw',
};

export const transactionStatus = {
    PAID: 'paid',
    PENDING: 'pending',
    ERROR: 'error',
    CONFIRMATION: 'confirmation',
    MIN_DEPOSIT_PENDING: 'min-deposit-pending',
    DISABLED: 'disabled',
};

export const lengthUrl = {
    max: 200,
};

export const toastType = {
    ERROR: 'error',
    INFO: 'info',
};

export const notificationType = {
    DANGER: 'danger',
    PRIMARY: 'primary',
    AUTO_LOGOUT: 'auto_logout',
};

export const AUTO_LOGOUT_TOAST_DURATION = 100000000;

export const USER_ACTIVITY_EVENTS = [
    'keydown',
    'click',
    'mousemove',
    'mousewheel',
    'touchstart',
    'touchmove',
];

export const WALLET_TABS = [
    'wallet',
    'trade-history',
    'activity-history',
    'dw-history',
    'active-orders',
];

export const TWITTER_URL = 'https://www.twitter.com';

export const TWEET_URL = `${TWITTER_URL}/intent/tweet`;

export const DISCORD_DEFAULT_URL = 'https://discord.gg/';

export const TELEGRAM_DEFAULT_URL = 'https://t.me/';

export const PHONE_VERIF_REQUEST_CODE_INTERVAL = 60;
export const EMAIL_VERIF_REQUEST_CODE_INTERVAL = 61;

export const TIMERS = {
    SEND_PHONE_CODE: 'phone_code_timer',
    SEND_EMAIL_CODE: 'email_code_timer',
    SEND_CURRENT_EMAIL_CODE: 'current_email_code_timer',
    SEND_NEW_EMAIL_CODE: 'new_email_code_timer',
};

export const SHARE_POST = 'share_post';
export const AIRDROP = 'airdrop';
export const BOUNTY = 'bounty';
export const TOKEN_SHOP = 'token_shop';
export const DISCORD_REWARDS = 'discord_rewards';
export const TOKEN_SIGNUP = 'token_signup';
export const COMMENT_TIP = 'comment_tip';
export const TIP_FEE_TYPE = 'fee';
export const TOKEN_PROMOTION = 'token_promotion';

export const TOKEN_SETTINGS_TABS = {
    general: 'general',
    promotion: 'promotion',
    advanced: 'advanced',
    deploy: 'deploy',
    markets: 'markets',
};

export const TOKEN_SETTINGS_PROMOTION_TABS = {
    bounty: 'bounty',
    token_shop: 'token_shop',
    airdrop: 'airdrop',
    discord_rewards: 'discord_rewards',
    signup_bonus: 'signup_bonus',
    token_promotion: 'token_promotion',
};

export const tokenReleaseChartColors = [
    '#474747',
    '#D0AF21',
];

export const PHONE_INPUT_PRIMARY_COLOR = '#D1B000';

export const NUMBER_ABBREVIATION_PRECISION = 2;

export const NUMBER_ABBREVIATION_UNIT = [
    'K',
    'M',
    'B',
    'T',
];

export const TARGET_VALUES = [
    '_blank',
    '_self',
    '_parent',
    '_top',
];

export const PANEL_DEV_ENV = 'dev';

export const AIRDROP_CLAIM_ERROR = {
    TWITTER_INVALID_TOKEN: 'invalid twitter token',
};

export const WALLET_WITHDRAW_ERROR = {
    INCORRECT_ADDRESS_START: 'incorrect address start',
    INCORRECT_ADDRESS_LENGTH: 'incorrect address length',
    SMART_CONTRACT_ADDRESS: 'smart contract address given',
};

export const REWARD_BOUNTIES_TEXT_AREA = {
    maxLength: 255,
    rows: 15,
};

export const WALLET_ITEMS_BATCH_SIZE = 11;

export const DEPLOY_PENDING_LS_KEY = 'deploy-pending-crypto';

export const MEDIA_BREAKPOINTS = Object.freeze({
    min_width: {
        md: 992,
        xlg: 1560,
    },
    max_width: {
        md: 991,
        xlg: 1559,
    },
});

export const MAX_FILE_BYTES_UPLOAD = 4194304;
export const MIB_BYTES = 1048576;

export const API_TIMEOUT = 500;

export const CHART_DEFAULT_DUMMY_DATA = {
    labels: ['0%', '0%'],
    data: [0, 0],
    borderColor: '#8dc63f',
};

export const CODE_LENGTH = {
    sms: 6,
    mail: 6,
};

export const CHART_POSITIVE_DUMMY_DATA = {
    labels: ['+10%', '-4%', '+20%', '-2%', '+78%'],
    data: [10, 6, 24, 22, 90],
    borderColor: '#8dc63f',
};

export const CHART_NEGATIVE_DUMMY_DATA = {
    labels: ['+78%', '-2%', '+20%', '-4%', '+10%'],
    data: [90, 22, 24, 6, 10],
    borderColor: '#d2000e',
};

export const TOKEN_FOLLOW_STATUS = {
    FOLLOWED: 'followed',
    UNFOLLOWED: 'unfollowed',
    NEUTRAL: 'neutral',
};

export const EXTERNAL_URL_TRUNCATE_LENGTH = 35;

export const REWARD_TITLE_TRUNCATE_LENGTH = 13;

export const VOLUNTEER_REMOVE_TYPE = 'reject';
export const VOLUNTEER_REFUND_TYPE = 'refund';

export const SLIDER_DEFAULT_MAX_AMOUNT = 100;

export const TRADE_ORDER_INPUT_FLAGS = {
    buyPrice: 'buy-order-price',
    buyAmount: 'buy-order-amount',
    buyTotalPrice: 'buy-order-total-price',
    sellPrice: 'sell-order-price',
    sellAmount: 'sell-order-amount',
    sellTotalPrice: 'sell-order-total-price',
};

/** Error for when Services ara unavailable (viabtc, gateway, etc) */
export class ServiceUnavailableError extends Error { }

export const RANK_WREATHS = {
    1: 'wreath-gold.png',
    2: 'wreath-silver.png',
    3: 'wreath-bronze.png',
    4: 'wreath-blue.png',
    5: 'wreath-blue.png',
    6: 'wreath-blue.png',
    7: 'wreath-blue.png',
    8: 'wreath-blue.png',
    9: 'wreath-blue.png',
    10: 'wreath-blue.png',
};

export const RANK_MEDALS = {
    1: 'medal-1.png',
    2: 'medal-2.png',
    3: 'medal-3.png',
    4: 'medal-4.png',
    5: 'medal-5.png',
    6: 'medal-6.png',
    7: 'medal-7.png',
    8: 'medal-8.png',
    9: 'medal-9.png',
    10: 'medal-10.png',
};

export const LOGOUT_FORM_ID = 'logout-form';

export const KeyCode = {
    Tab: 9,
};
