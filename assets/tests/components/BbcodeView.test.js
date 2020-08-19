import {shallowMount, createLocalVue} from '@vue/test-utils';
import BbcodeView from '../../js/components/bbcode/BbcodeView';
import VueSanitize from 'vue-sanitize';
import {sanitizeOptions, ourDomains} from '../../js/utils/constants.js';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(VueSanitize, sanitizeOptions, ourDomains);
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
            propsData: {value: '[url=foo]bar[/url], '
                + '[url=https://www.mintme.com/kb/How-to-delete-an-airdrop-campaign]How to delete an airdrop campaign?[/url], '
                + '[url=https://www.server.trading/en/offer/hosting]Hosting[/url], '
                + '[url=https://www.google.com/]google[/url], '
                + '[url=https://www.zzz.com.ua/ru/loginmysql]ZZZ MySQL[/url], '
                + '[url=https://www.for.ug/en/offer/websites]FOG Websites[/url], '
                + '[url=test.com]Test link[/url], '
                + '[url=https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/replace]replace function[/url], '
                + '[url=https://www.hit.ng/panel/en/register]HIT registration[/url], '
                + '[url=https://www.mintme.com/explorer/richlist]Explorer[/url]'},
        });

        expect(wrapper.vm.parsedValue).toBe('<a href="foo" rel="noreferrer" target="_blank">bar</a>, '
            + '<a href="https://www.mintme.com/kb/How-to-delete-an-airdrop-campaign">How to delete an airdrop campaign?</a>, '
            + '<a href="https://www.server.trading/en/offer/hosting">Hosting</a>, '
            + '<a href="https://www.google.com/" rel="noreferrer" target="_blank">google</a>, '
            + '<a href="https://www.zzz.com.ua/ru/loginmysql">ZZZ MySQL</a>, '
            + '<a href="https://www.for.ug/en/offer/websites">FOG Websites</a>, '
            + '<a href="test.com" rel="noreferrer" target="_blank">Test link</a>, '
            + '<a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/replace" rel="noreferrer" target="_blank">replace function</a>, '
            + '<a href="https://www.hit.ng/panel/en/register">HIT registration</a>, '
            + '<a href="https://www.mintme.com/explorer/richlist">Explorer</a>');
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
