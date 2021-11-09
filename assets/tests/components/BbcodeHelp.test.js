import {createLocalVue, shallowMount} from '@vue/test-utils';
import BbcodeHelp from '../../js/components/bbcode/BbcodeHelp';

let propsForTestCorrectlyRenders = {
    placement: 'bottom',
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('html-sanitize', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('BbcodeHelp', () => {
    it('should parse and transform BBCode to HTML/CSS when the function parse() is called', () => {
        const wrapper = shallowMount(BbcodeHelp, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });

        expect(wrapper.vm.parse('[b][/b]')).toEqual(
            expect.stringContaining('bold')
        );
        expect(wrapper.vm.parse('[i][/i]')).toEqual(
            expect.stringContaining('italic')
        );
        expect(wrapper.vm.parse('[u][/u]')).toEqual(
            expect.stringContaining('underline')
        );
        expect(wrapper.vm.parse('[s][/s]')).toEqual(
            expect.stringContaining('<span')
        );
        expect(wrapper.vm.parse('[ol][/ol]')).toEqual(
            expect.stringContaining('<ol')
        );
        expect(wrapper.vm.parse('[li][/li]')).toEqual(
            expect.stringContaining('<li')
        );
        expect(wrapper.vm.parse('[p][/p]')).toEqual(
            expect.stringContaining('<p')
        );
    });

    it('should replace "<a href=" with the required HTML/CSS string when the function parse() is called', () => {
        const wrapper = shallowMount(BbcodeHelp, {
            propsData: propsForTestCorrectlyRenders,
            localVue: mockVue(),
        });

        expect(wrapper.vm.parse('[url][/url]')).toEqual(
            expect.stringContaining('<a style="pointer-events: none;" href="')
        );
    });
});
