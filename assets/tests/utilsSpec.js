import {deepFlatten, isValidUrl} from '../js/utils/utils';

describe('utils', () => {
    describe('#deepFlatten()', () => {
        const expectedResult = [1, 2, 3, 4, 5];

        it('got {Array} and returns flatten', () => {
            let arr = [1, 2, [3, [4, 5]]];

            expect(deepFlatten(arr)).to.deep.equal(expectedResult);
        });

        it('got {Object} and returns flatten', () => {
            let obj = {foo: 1, bar: {foo: 2, bar: 3, baz: {foo: 4, bar: 5}}};

            expect(deepFlatten(obj)).to.deep.equal(expectedResult);
        });

        it('got mixed({Object}|{Array}) and returns flatten', () => {
            let obj = {foo: 1, bar: [2, 3, {foo: 4, bar: 5}]};

            expect(deepFlatten(obj)).to.deep.equal(expectedResult);
        });
    });

    describe('#isValidUrl()', () => {
        it('returns true', () => {
            expect(isValidUrl('https://example.com')).to.be.true;
            expect(isValidUrl('https://www.example.com')).to.be.true;
            expect(isValidUrl('ftp://example.com/foo/bar')).to.be.true;
            expect(isValidUrl('ftp://example.com:80/foo/bar')).to.be.true;
            expect(isValidUrl('http://example.com:8000')).to.be.true;
            expect(isValidUrl('http://example.com.ua/foo+bar')).to.be.true;
        });

        it('returns false', () => {
            expect(isValidUrl('example.com:80/foo/bar')).to.be.false;
            expect(isValidUrl('http://example.com:80foo00')).to.be.false;
            expect(isValidUrl('httpp://example.com.ua/foo+bar')).to.be.false;
        });
    });
});
