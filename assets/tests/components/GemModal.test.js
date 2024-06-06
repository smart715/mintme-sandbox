import {shallowMount, createLocalVue} from '@vue/test-utils';
import GemModal, {GEM_MODAL_LS_KEY} from '../../js/components/modal/GemModal';

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

    return localVue;
}

describe('GemModal', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(GemModal, {
            localVue: localVue,
            propsData: {
                articleUrl: 'http://localhost/',
                appearanceDelay: 1000,
                maxViews: 5,
            },
        });
    });

    afterEach(() => {
        localStorage.removeItem(GEM_MODAL_LS_KEY);
    });

    it('opens modal on appearanceDelay', () => {
        jest.useFakeTimers();
        expect(wrapper.vm.isOpened).toBe(false);
        jest.advanceTimersByTime(wrapper.vm.appearanceDelay);
        wrapper.vm.openModal();
        expect(wrapper.vm.isOpened).toBe(true);
    });

    it('does not open modal when appearance delay has not passed', () => {
        localStorage.setItem(GEM_MODAL_LS_KEY, '2');

        jest.useFakeTimers();
        jest.advanceTimersByTime(wrapper.vm.appearanceDelay - 1);

        expect(wrapper.vm.isOpened).toBe(false);
    });

    it('increments views when modal is opened', () => {
        wrapper.vm.openModal();
        expect(localStorage.getItem(GEM_MODAL_LS_KEY)).toBe('1');
    });

    it('calls readMore method and sets maxViews in local storage when articleUrl is defined', () => {
        const readMoreButton = wrapper.findComponent('button');
        readMoreButton.trigger('click');

        expect(window.location.href).toBe(wrapper.vm.articleUrl);
        expect(localStorage.getItem(GEM_MODAL_LS_KEY)).toBe(String(wrapper.vm.maxViews));
    });

    it('should set isClosing to true', () => {
        wrapper.vm.closeModal();
        expect(wrapper.vm.isClosing).toBe(true);
    });

    it('should set isOpened and isClosing to false after animation duration', () => {
        jest.useFakeTimers();

        wrapper.vm.closeModal();

        jest.advanceTimersByTime(wrapper.vm.GEM_MODAL_ANIMATION_DURATION_MS);

        expect(wrapper.vm.isOpened).toBe(true);
        expect(wrapper.vm.isClosing).toBe(false);
    });
});
