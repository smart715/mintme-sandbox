import {mount} from '@vue/test-utils';
import BbcodeView from '../../js/components/bbcode/BbcodeView';

describe('BbcodeView', () => {
    it('parse bbcode', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {description: '[h1]Lorem ipsum.[/h1]'},
        });

        expect(wrapper.vm.parsedDescription).to.equal('<h1>Lorem ipsum.</h1>');
    });

    it('parse bbcode image', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {description: '[img]foo[/img]'},
        });

        expect(wrapper.vm.parsedDescription).to.equal('<img style="max-width: 100%;" src="foo"/>');
    });

    it('parse bbcode link', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {description: '[url=foo]bar[/url]'},
        });

        expect(wrapper.vm.parsedDescription).to.equal('<a rel="nofollow" href="foo">bar</a>');
    });
});
