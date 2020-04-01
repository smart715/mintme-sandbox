import {shallowMount} from '@vue/test-utils';
import BbcodeHelp from '../../js/components/bbcode/BbcodeHelp';

let propsForTestCorrectlyRenders = {
    placement: {type: 'foo', default: 'bottom'},
};

describe('BbcodeHelp', () => {
    it('should parse and transform BBCode to HTML/CSS when the function parse() is called', () => {
        const wrapper = shallowMount(BbcodeHelp, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.parse('[b][/b]')).to.contain('bold');
        expect(wrapper.vm.parse('[i][/i]')).to.contain('italic');
        expect(wrapper.vm.parse('[u][/u]')).to.contain('underline');
        expect(wrapper.vm.parse('[s][/s]')).to.contain('<span');
        expect(wrapper.vm.parse('[ol][/ol]')).to.contain('<ol');
        expect(wrapper.vm.parse('[li][/li]')).to.contain('<li');
        expect(wrapper.vm.parse('[p][/p]')).to.contain('<p');
    });

    it('should replace "<a href=" with the required HTML/CSS string when the function parse() is called', () => {
        const wrapper = shallowMount(BbcodeHelp, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.parse('[url][/url]')).to.contain('<a style="pointer-events: none;" href="');
    });
});
