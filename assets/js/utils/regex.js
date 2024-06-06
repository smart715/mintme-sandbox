import TOP_LEVEL_DOMAINS from './top-level-domains';

export const REGEX_URL = new RegExp(
    '((http\|HTTP)((s\|S))?:\\/\\/.)?((www\|WWW)\\.)?[-a-zA-Z0-9@:%._\\+~#=]' +
    '{0,256}\\.('+ TOP_LEVEL_DOMAINS.join('\|') + TOP_LEVEL_DOMAINS.join('\|').toLowerCase() + ')\\b' +
    '([-a-zA-Z0-9@:%_\\+.~#?&//=]*)',
    'g'
);

export const REGEX_YOUTUBE = new RegExp(
    '^((?:https?:)?\\/\\/)?((?:www\|m)\\.)?' +
    '((?:youtube\\.com\|youtu.be))(\\/(?:[\\w\\-]+\\?v=\|embed\\/\|v\\/)?)' +
    '([\\w\\-]+)(\\S+)?$',
    'gm'
);

export const REGEX_YOUTUBE_CHANNEL = new RegExp(
    '^((?:https?:)?\\/\\/)?((?:www\|m)\\.)?' +
    'youtube\\.com\\/(channel\|user\|c)\\/[\\w\\-]+',
    'gm'
);

export const REGEX_YOUTUBE_URL_ID = new RegExp(
    '^.*(?:(?:youtu\\.be\\/\|v\\/\|vi\\/\|u\\/\\w\\/' +
    '\|embed\\/\|shorts\\/)\|(?:(?:watch)?\\?v(?:i)?' +
    '=\|\\&v(?:i)?=))([^#\\&\\?]*).*'
);

export const REGEX_IMAGE = new RegExp('\.(jpeg\|jpg\|gif\|png\|svg)$', 'gm');

export const REGEX_YOUTUBE_CHANNEL_ID = new RegExp(
    '(?:https|http)\\:\\/\\/(?:[\\w]+\\.)?' +
    'youtube\\.com\\/(?:c\\/|channel\\/|user\\/)?' +
    '([\\w-]+)\\/?(?:(featured|videos|playlists|about|))'
);

export const REGEX_OLD_NEWS_LOCALE = /^https?:\/\/[^/]+\/(?:[a-z]{2}\/)?news\b/;

export const REGEX_NEW_NEWS_LOCALE = /\/[a-z]{2}\//;

export const TEXT_HASHTAG = /#([\p{L}\p{N}_]{1,100})(?=[^_#\p{L}\p{N}]|$)/gu;
