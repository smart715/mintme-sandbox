import {deepFlatten, isValidUrl, assertUniquePropertyValuesInObjectArray} from '../js/utils';

describe('utils', () => {
    describe('#deepFlatten()', () => {
        const expectedResult = [1, 2, 3, 4, 5];

        it('got {Array} and returns flatten', () => {
            let arr = [1, 2, [3, [4, 5]]];

            expect(deepFlatten(arr)).toEqual(expectedResult);
        });

        it('got {Object} and returns flatten', () => {
            let obj = {foo: 1, bar: {foo: 2, bar: 3, baz: {foo: 4, bar: 5}}};

            expect(deepFlatten(obj)).toEqual(expectedResult);
        });

        it('got mixed({Object}|{Array}) and returns flatten', () => {
            let obj = {foo: 1, bar: [2, 3, {foo: 4, bar: 5}]};

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

    describe('#assertUniquePropertyValuesInObjectArray()', () => {
        it('returns true if there are not duplicate values for some property in an object array', () => {
            let arr = [
                {foo: 'bar'},
                {foo: 'baz'},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(true);
        });

        it('returns false if there are duplicate values for some property in an object array', () => {
            let arr = [
                {foo: 'bar'},
                {foo: 'bar'},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(false);
        });

        it('does not take into account empty values', () => {
            let arr = [
                {foo: ''},
                {foo: ''},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo')).toBe(true);
        });

        it('takes into account empty values if excludeEmpty is false', () => {
            let arr = [
                {foo: ''},
                {foo: ''},
            ];

            expect(assertUniquePropertyValuesInObjectArray(arr, 'foo', false)).toBe(false);
        });
    });
});
