/**
 * Results of the profanity check
 * @typedef {Object} CensorResult
 * @property {boolean} isClean - Whether the word is clean
 * @property {string} badWord - The profanity word if the word is not clean
 */
class CensorResult {
    /**
     * Constructor
     * @param {string|null} badWord - The profanity word if the word is not clean
     * @param {boolean} isClean - Whether the word is clean
     */
    constructor(badWord = null, isClean = true) {
        this.badWord = badWord;
        this.isClean = isClean;
    }
}

export default CensorResult;
