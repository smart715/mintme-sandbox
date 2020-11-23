/**
 * interpolate message for translation
 * @class
 */
export default class CustomFormatter {
    /**
     * @param {string} translation
     * @param {object} context
     * @return {string} String with interpolated translation
     */
    interpolate(translation, context) {
        if ('string' === typeof translation) {
            return String(translation);
        }

        let result = translation
            .replaceAll('&#039;', '\'')
            .replaceAll('&quot;', '"')
            .replaceAll('&amp;', '&')
            .replaceAll('&lt;', '<')
            .replaceAll('&gt;', '>');

        if (typeof context === 'object') {
            const matches = result.match(/(%([^%]|%%)*%)/g);

            if (matches) {
                matches.forEach((match) => {
                    const prop = match.replace(/[%]+/g, '');

                    if (!context || !Object.prototype.hasOwnProperty.call(context, prop)) {
                        return;
                    }

                    const regex = new RegExp(match, 'g');
                    result = result.replace(regex, context[prop]);
                });
            }
        }

        return result;
    }
}
