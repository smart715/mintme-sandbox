import {shallowMount, createLocalVue} from '@vue/test-utils';
import CopyLink from '../../js/components/CopyLink';

const localVue = mockVue();

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
    localVue.directive('clipboard', {});
    localVue.directive('tippy', {});

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} store
 * @return {Wrapper<Vue>}
 */
function mockCopyLink(props = {}, store = {}) {
    return shallowMount(CopyLink, {
        localVue: localVue,
        propsData: createSharedTestProps(props),
        directives: {
            clipboard: {},
            tippy: {},
        },
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        contentToCopy: 'foo',
        ...props,
    };
}

describe('CopyLink', () => {
    it('renders correctly with different props', async () => {
        const wrapper = mockCopyLink();

        expect(wrapper.vm.contentToCopy).toBe('foo');

        await wrapper.setProps({
            contentToCopy: 'bar',
        });

        expect(wrapper.vm.contentToCopy).toBe('bar');
    });

    it('triggers on copy success', async () => {
        jest.useFakeTimers();

        const tippyMock = {
            hide: jest.fn(),
        };
        const wrapper = mockCopyLink();

        wrapper.vm.$el._tippy = tippyMock;

        wrapper.vm.onCopy('e');
        expect(wrapper.vm.tooltipMessage).toBe('copy_link.copied');

        jest.advanceTimersByTime(1500);
        await wrapper.vm.$nextTick();

        expect(tippyMock.hide).toHaveBeenCalled();
    });

    it('triggers on copy error', async () => {
        jest.useFakeTimers();

        const tippyMock = {
            hide: jest.fn(),
        };
        const wrapper = mockCopyLink();

        wrapper.vm.$el._tippy = tippyMock;

        wrapper.vm.onError('e');
        expect(wrapper.vm.tooltipMessage).toBe('copy_link.press_ctrl_c');

        jest.advanceTimersByTime(1500);
        await wrapper.vm.$nextTick();

        expect(tippyMock.hide).toHaveBeenCalled();
    });

    it('should hide the tooltip', async () => {
        jest.useFakeTimers();

        const wrapper = mockCopyLink();

        const tippyMock = {
            hide: jest.fn(),
        };

        wrapper.vm.$el._tippy = tippyMock;

        wrapper.vm.hideTooltip();

        expect(tippyMock.hide).not.toHaveBeenCalled();

        jest.advanceTimersByTime(1500);

        await wrapper.vm.$nextTick();

        expect(tippyMock.hide).toHaveBeenCalled();
    });
});
