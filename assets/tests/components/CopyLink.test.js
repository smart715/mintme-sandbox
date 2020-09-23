import {shallowMount, createLocalVue} from '@vue/test-utils';
import CopyLink from '../../js/components/CopyLink';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('clipboard', {});
    localVue.directive('tippy', {});
    return localVue;
}

describe('CopyLink', () => {
    let copyLink = shallowMount(CopyLink, {
        propsData: {contentToCopy: 'foo'},
        localVue: mockVue(),
    });
    it('renders correctly with different props', () => {
        expect(copyLink.vm.contentToCopy).toBe('foo');

        copyLink = shallowMount(CopyLink, {
            propsData: {contentToCopy: 'bar'},
            localVue: mockVue(),
        });
        expect(copyLink.vm.contentToCopy).toBe('bar');
    });
    it('triggers on copy success', () => {
        copyLink.vm.onCopy('e');
        expect(copyLink.vm.tooltipMessage).toBe('Copied!');
    });
    it('triggers on copy error', () => {
        copyLink.vm.onError('e');
        expect(copyLink.vm.tooltipMessage).toBe('Press Ctrl+C to copy');
    });
});
