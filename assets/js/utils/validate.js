/**
 * check for dashes or spaces in the beggining of token name
 * @param {string} value
 */
function correctTokenName(value) {
    const matches = value.match(/^[-\s]+/);

    if (null === matches) {
        return true;
    }

    return 0 === matches.length;
}

export {
    correctTokenName,
};
