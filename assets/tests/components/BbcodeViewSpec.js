import Vue from 'vue';
import {mount} from '@vue/test-utils';
import BbcodeView from '../../js/components/bbcode/BbcodeView';
import VueSanitize from 'vue-sanitize';
import {sanitizeOptions} from '../../js/utils/constants.js';

Vue.use(VueSanitize, sanitizeOptions);

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

        expect(wrapper.vm.parsedValue).to.equal('<img style="max-width:100%" src="foo" />');
    });

    it('parse bbcode link', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {value: '[url=foo]bar[/url]'},
        });

        expect(wrapper.vm.parsedValue).to.equal('<a rel="nofollow" target="_blank" href="https://foo">bar</a>');
    });

    it('parse bbcode li', () => {
        const wrapper = mount(BbcodeView, {
            propsData: {value: '[li]foo[/li]'},
        });

        expect(wrapper.vm.parsedValue).to.equal('<li><span class="bbcode-span-list-item">foo</span></li>');
    });

    it('parse bbcode not allowed tags', () => {
        const wrapper = mount(BbcodeView, {
            propsData: {value: '<div>bar</div>'},
        });

        expect(wrapper.vm.parsedValue).to.equal('bar');
    });
});
