import Censor from '../js/utils/profanity/censor';
import CensorConfig from '../js/utils/profanity/censorConfig';

const expectedResponse = {
    censorChecks: ['/(f|f\\.|f\\-|ƒ)(u|u\\.|u\\-|υ|µ)(c|c\\.|c\\-|Ç|ç|¢|€|<|\\(|{|©)(k|k\\.|k\\-|Κ|κ)/i'],
};

describe('Censor', function() {
    describe('IsClean is true', function() {
        const censor = new Censor(new CensorConfig(expectedResponse));

        it('should be clean if the word in between other words', function() {
            const result = censor.isClean('TESTfuckTEST');
            expect(result.isClean).toBe(true);
            expect(result.badWord).toBe(null);
        });
    });

    describe('IsClean is false', function() {
        const censor = new Censor(new CensorConfig(expectedResponse));

        it('shouldn\'t be clean if word is seperate between words', function() {
            const result = censor.isClean('TEST fuck TEST');
            expect(result.isClean).toBe(false);
            expect(result.badWord).toBe('fuck');
        });

        it('shouldn\'t be clean if word captalized', function() {
            const result = censor.isClean('FUCK');
            expect(result.isClean).toBe(false);
            expect(result.badWord).toBe('FUCK');
        });

        it('shouldn\'t be clean if word is manipulated to escape censor', function() {
            const result = censor.isClean('f.u.c.k');
            expect(result.isClean).toBe(false);
            expect(result.badWord).toBe('f.u.c.k');
        });
    });
});
