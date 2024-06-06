import stripHTMLMixin from '../../js/mixins/filters/stripHTML';

describe('stripHTMLMixin', () => {
    const stripHTML = stripHTMLMixin.filters.stripHTML;
    it('should remove html tags', () => {
        expect(stripHTML('<p>test</p>')).toBe('test');
        expect(stripHTML('<p>test</p><p>test</p>')).toBe('testtest');
        expect(stripHTML('<span>test</span>')).toBe('test');
        expect(stripHTML('<span>test</span><span>test</span>')).toBe('testtest');
        expect(stripHTML('<div>test</div>')).toBe('test');
        expect(stripHTML('<div>test</div><div>test</div>')).toBe('testtest');
        expect(stripHTML('<p>   test   </p>')).toBe('   test   ');
    });

    it('should strip html with attributes', () => {
        expect(stripHTML('<p style="color: black">test</p>')).toBe('test');
        expect(stripHTML('<p style="color: black">test</p><p style="color: black">test</p>')).toBe('testtest');
        expect(stripHTML('<span style="color: black">test</span>')).toBe('test');
        expect(stripHTML('<p style="color: black">   test   </p>')).toBe('   test   ');
    });
});
