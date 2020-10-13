import {shallowMount, createLocalVue} from '@vue/test-utils';
import BbcodeView from '../../js/components/bbcode/BbcodeView';
import VueSanitize from 'vue-sanitize';
import {sanitizeOptions} from '../../js/utils/constants.js';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(VueSanitize, sanitizeOptions);
    return localVue;
}

describe('BbcodeView', () => {
    it('parse bbcode', () => {
        const wrapper = shallowMount(BbcodeView, {
            localVue: mockVue(),
            propsData: {value: '[h1]Lorem ipsum.[/h1]'},
        });

        expect(wrapper.vm.parsedValue).toBe('<h1>Lorem ipsum.</h1>');
    });

    it('parse bbcode image', () => {
        const wrapper = shallowMount(BbcodeView, {
            localVue: mockVue(),
            propsData: {value: '[img]foo[/img]'},
        });

        expect(wrapper.vm.parsedValue).toBe('<img style="max-width:100%" src="foo" />');
    });

    it('parse bbcode link', () => {
        const wrapper = shallowMount(BbcodeView, {
            localVue: mockVue(),
            propsData: {value: '[url=foo]bar[/url]'},
        });

        expect(wrapper.vm.parsedValue).toBe('<a rel="noopener" target="_blank" href="https://foo">bar</a>');
    });

    it('parse bbcode li', () => {
        const wrapper = shallowMount(BbcodeView, {
            localVue: mockVue(),
            propsData: {value: '[li]foo[/li]'},
        });

        expect(wrapper.vm.parsedValue).toBe('<li><span class="bbcode-span-list-item">foo</span></li>');
    });

    it('parse bbcode not allowed tags', () => {
        const wrapper = shallowMount(BbcodeView, {
            localVue: mockVue(),
            propsData: {value: '<button>bar</button>'},
        });

        expect(wrapper.vm.parsedValue).toBe('bar');
    });
});
