import {shallowMount, createLocalVue} from '@vue/test-utils';
import CopyLink from '../../js/components/CopyLink';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
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
        expect(copyLink.vm.tooltipMessage).toBe('copy_link.copied');
    });
    it('triggers on copy error', () => {
        copyLink.vm.onError('e');
        expect(copyLink.vm.tooltipMessage).toBe('copy_link.press_ctrl_c');
    });
});
