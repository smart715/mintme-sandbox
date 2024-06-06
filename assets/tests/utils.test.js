import {
    deepFlatten,
    isValidUrl,
    assertUniquePropertyValuesInObjectArray,
    rtrimZeros,
    toMoneyWithTrailingZeroes,
    isValidTelegramUrl,
    toIntegerWithSpaces,
    generateCoinAvatarHtml,
    generateMintmeAvatarHtml,
} from '../js/utils';

describe('utils', () => {
    describe('isValidTelegramUrl', () => {
        it('should return true for valid telegram url', () => {
            expect(isValidTelegramUrl('https://t.me/joinchat/foobar')).toBe(true);
        });

        it('should return false for invalid telegram url with special chars', () => {
            expect(isValidTelegramUrl('https://t.me/joinchat/foobar$_.+!*(),')).toBe(false);
        });

        it('should return false for invalid telegram url', () => {
            expect(isValidTelegramUrl('https://t.me/')).toBe(false);
        });

        it('should return true for telegram url with - and _', () => {
            expect(isValidTelegramUrl('https://t.me/+foo-bar_s')).toBe(true);
        });

        it('should return try for valid telegram url with + instead of joinchat/', () => {
            expect(isValidTelegramUrl('https://t.me/+foobar')).toBe(true);
        });
    });

    describe('#deepFlatten()', () => {
        const expectedResult = [1, 2, 3, 4, 5];

        it('got {Array} and returns flatten', () => {
            const arr = [1, 2, [3, [4, 5]]];

            expect(deepFlatten(arr)).toEqual(expectedResult);
        });

        it('got {Object} and returns flatten', () => {
            const obj = {foo: 1, bar: {foo: 2, bar: 3, baz: {foo: 4, bar: 5}}};

            expect(deepFlatten(obj)).toEqual(expectedResult);
        });

        it('got mixed({Object}|{Array}) and returns flatten', () => {
            const obj = {foo: 1, bar: [2, 3, {foo: 4, bar: 5}]};

            expect(deepFlatten(obj)).toEqual(expectedResult);
        });
    });

    describe('#isValidUrl()', () => {
        it('returns true', () => {
            expect(isValidUrl('https://example.com')).toBe(true);
            expect(isValidUrl('https://www.example.com')).toBe(true);
            expect(isValidUrl('ftp://example.com/foo/bar')).toBe(true);
            expect(isValidUrl('ftp://example.com:80/foo/bar')).toBe(true);
            expect(isValidUrl('http://example.com:8000')).toBe(true);
            expect(isValidUrl('http://example.com.ua/foo+bar')).toBe(true);
        });

        it('returns false', () => {
            expect(isValidUrl('example.com:80/foo/bar')).toBe(false);
            expect(isValidUrl('http://example.com:80foo00')).toBe(false);
            expect(isValidUrl('httpp://example.com.ua/foo+bar')).toBe(false);
        });
    });
    describe('#rtrimZeros()', () => {
        it('remove zeros from the right of string if existed ', () => {
            expect(rtrimZeros('0.0000')).toBe('0');
            expect(rtrimZeros('0.00001')).toBe('0.00001');
            expect(rtrimZeros('0.000001')).toBe('0.000001');
            expect(rtrimZeros('10.0200000')).toBe('10.02');
        });
    });

    describe('toMoneyWithTrailingZeroes', () => {
        const dataProvider = [
            {value: 0, expected: '0.00000000'},
            {value: 0.1, expected: '0.10000000'},
            {value: 0.01, expected: '0.01000000'},
            {value: 10000000000, expected: '10000000000.00000000'},
            {value: 100000000000, expected: '100000000000.00000000'},
            {value: 1, expected: '1.00000000'},
            {value: 1.1, expected: '1.10000000'},
            {value: -1.01, expected: '-1.01000000'},
        ];

        dataProvider.forEach((data) => {
            it(`should return ${data.expected} for ${data.value}`, () => {
                expect(toMoneyWithTrailingZeroes(data.value)).toBe(data.expected);
            });
        });
    });

    describe('toMoneyWithTrailingZeroesWithSubunit', () => {
        const dataProvider = [
            {value: 0, subunit: 4, expected: '0.0000'},
            {value: 0.1, subunit: 6, expected: '0.100000'},
            {value: 0.01, subunit: 3, expected: '0.010'},
            {value: 10000000000, subunit: 2, expected: '10000000000.00'},
            {value: 100000000000, subunit: 6, expected: '100000000000.000000'},
            {value: 1, subunit: 4, expected: '1.0000'},
            {value: 1.1, subunit: 8, expected: '1.10000000'},
            {value: -1.01, subunit: 4, expected: '-1.0100'},
        ];

        dataProvider.forEach((data) => {
            it(`should return ${data.expected} for ${data.value}, ${data.subunit}`, () => {
                expect(toMoneyWithTrailingZeroes(data.value, data.subunit)).toBe(data.expected);
            });
        });
    });

    describe('#assertUniquePropertyValuesInObjectArray()', () => {
        it('returns true if there are not duplicate values for some property in an object array', () => {
            const arr = [
                {foo: 'bar'},
                {foo: 'baz'},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(true);
        });

        it('returns false if there are duplicate values for some property in an object array', () => {
            const arr = [
                {foo: 'bar'},
                {foo: 'bar'},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(false);
        });

        it('does not take into account empty values', () => {
            const arr = [
                {foo: ''},
                {foo: ''},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(true);
        });

        it('takes into account empty values if excludeEmpty is false', () => {
            const arr = [
                {foo: ''},
                {foo: ''},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo', false)).toBe(false);
        });
    });

    describe('toIntegerWithSpaces', () => {
        it('return a number with a space every 3 digits ', () => {
            expect(toIntegerWithSpaces('0.0000')).toBe('0.0 000');
            expect(toIntegerWithSpaces(1)).toBe('1');
            expect(toIntegerWithSpaces('1')).toBe('1');
            expect(toIntegerWithSpaces(1.0000)).toBe('1');
            expect(toIntegerWithSpaces(1.0001)).toBe('1.0 001');
            expect(toIntegerWithSpaces(100)).toBe('100');
            expect(toIntegerWithSpaces(1000)).toBe('1 000');
            expect(toIntegerWithSpaces(10000)).toBe('10 000');
            expect(toIntegerWithSpaces(100000)).toBe('100 000');
            expect(toIntegerWithSpaces(1000000)).toBe('1 000 000');
        });
    });

    describe('generateCoinAvatarHtml()', () => {
        it('generate coin avatar by symbol', () => {
            expect(generateCoinAvatarHtml({
                symbol: 'CRO',
                isCrypto: true,
                withSymbol: false,
            }).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"[object Object]\"
                    class=\"rounded-circle coin-avatar-sm\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });

        it('generate coin avatar by image', () => {
            expect(generateCoinAvatarHtml({
                image: 'BNB_avatar.svg',
                isUserToken: true,
                withSymbol: false,
            }).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"BNB_avatar.svg\"
                    class=\"rounded-circle coin-avatar-sm\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });

        it('generate avatar', () => {
            expect(generateCoinAvatarHtml({symbol: 'CRO', isCrypto: true}).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"[object Object]\"
                    class=\"rounded-circle coin-avatar-sm\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });

        it('generate mintme avatar', () => {
            expect(generateMintmeAvatarHtml(false).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"/media/default_mintme.svg\"
                    class=\"rounded-circle coin-avatar-sm coin-avatar-mintme\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });

        it('should return default coin avatar if image or symbol is empty', () => {
            expect(generateCoinAvatarHtml({isUserToken: true, withSymbol: false}).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"/media/default_token.png\"
                    class=\"rounded-circle coin-avatar-sm\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });

        it('add some classes', () => {
            expect(generateCoinAvatarHtml({
                isUserToken: true,
                classes: 'coin-avatar-lg',
                withSymbol: false,
            }).replace(/( |\n)/g, ''))
                .toBe(`<span class=\"coin-avatar\">
                <img
                    alt=\"avatar\"
                    src=\"/media/default_token.png\"
                    class=\"rounded-circle coin-avatar-lg\"
                />
            </span>`.replace(/( |\n)/g, ''));
        });
    });
});
