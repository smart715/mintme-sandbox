import CensorResult from './censorResult';

/**
 * Handles the censoring of text.
 * @param {object} config The configuration for the censor.
 */
class Censor {
    /**
     * Constructor
     * @param {CensorConfig} config
     */
    constructor(config) {
        this.censorChecks = config.censorChecks;
        this.whiteList = config.whitelist;
    }

    /**
     * Check if a string contains any of the words in the blacklist
     * @param {string} text
     * @return {CensorResult}
     **/
    isClean(text) {
        for (let patternIndex = 0; patternIndex < this.censorChecks.length; patternIndex++) {
            const pattern = this.censorChecks[patternIndex].slice(1, -2);
            const regex = new RegExp('\\b' + pattern + '\\b', 'i');
            const match = text.match(regex);
            if (null !== match) {
                if (this._isWordWhiteListed(match[0])) {
                    continue;
                }

                return new CensorResult(match[0], false);
            }
        }
        return new CensorResult();
    }

    /**
     * Check if a word is white listed
     * The approach matches backend package, It's suboptimal, but can't be helped without changing backend package
     * @param {string} word
     * @return {boolean}
     */
    _isWordWhiteListed(word) {
        const findIndex = this.whiteList.findIndex(
            (whitelistedWord) => whitelistedWord.toLowerCase() === word.toLowerCase()
        );

        return -1 !== findIndex;
    }
}

export default Censor;
