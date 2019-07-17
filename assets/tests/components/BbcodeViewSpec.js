import {mount} from '@vue/test-utils';
import BbcodeView from '../../js/components/BbcodeView';

describe('BbcodeView', () => {

    it('parse bbcode', () => {
        const wrapper = mount(BbcodeView, {
             propsData: {description: '[h1]Lorem ipsum.[/h1]'},
        });

        expect(wrapper.vm.parsedDescription).to.equal('<h1>Lorem ipsum.</h1>');
    });
});
