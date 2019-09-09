/**
 * check for dashes or spaces in the beggining of token name
 * @param {string} value
 * @return {bool}
 */
function validTokenName(value) {
    const matches = value.match(/^[-\s]+/);

    return null === matches || 0 === matches.length;
}

export {
    validTokenName,
};
