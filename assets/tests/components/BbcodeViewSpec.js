import {mount} from '@vue/test-utils';
import BbcodeView from '../../js/components/bbcode/BbcodeView';

describe('BbcodeView', () => {
    it('parse bbcode', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {value: '[h1]Lorem ipsum.[/h1]'},
        });

        expect(wrapper.vm.parsedValue).to.equal('<h1>Lorem ipsum.</h1>');
    });

    it('parse bbcode image', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {value: '[img]foo[/img]'},
        });

        expect(wrapper.vm.parsedValue).to.equal('<img style="max-width: 100%;" src="foo"/>');
    });

    it('parse bbcode link', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {value: '[url=foo]bar[/url]'},
        });

        expect(wrapper.vm.parsedValue).to.equal('<a rel="nofollow" target="_blank" href="https://foo">bar</a>');
    });
    it('parse bbcode xss protection', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {value: '<script>alert("XSS");</script>'},
        });

        expect(wrapper.vm.parsedValue).to.equal('&lt;script&gt;alert("XSS");&lt;/script&gt;');
    });
});
