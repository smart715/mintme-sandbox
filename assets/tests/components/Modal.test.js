import {createLocalVue, shallowMount} from '@vue/test-utils';
import Modal from '../../js/components/modal/Modal';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    return createLocalVue();
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        ...props,
    };
}


describe('Modal', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Modal, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('shouldn\'t be visible when visible props is false', async () => {
        await wrapper.setProps({
            visible: false,
            noClose: false,
            withoutPadding: false,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('visible')).toBe(undefined);
    });

    it('should be visible when visible props is true', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: false,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('visible')).toBe('true');
    });

    it('enable closing on ESC and enable closing on backdrop click when noClose props is false', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: false,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('nocloseonbackdrop')).toBe(undefined);
        expect(wrapper.findComponent('b-modal-stub').attributes('nocloseonesc')).toBe(undefined);
    });

    it('disable closing on ESC and disable closing on backdrop click when noClose props is true', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: true,
            withoutPadding: false,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('nocloseonbackdrop')).toBe('true');
        expect(wrapper.findComponent('b-modal-stub').attributes('nocloseonesc')).toBe('true');
    });

    it('a padding should be present when withoutPadding props is false', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: false,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('bodyclass')).toBe('');
        expect(wrapper.findComponent('.modal-body').attributes('class')).toBe('modal-body');
    });

    it('a padding should be absent when withoutPadding props is true', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: true,
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('bodyclass')).toBe('m-0 p-0');
        expect(wrapper.findComponent('.modal-body').attributes('class')).toBe('modal-body m-0 p-0');
    });

    it('emit "close" when clicking on <a>', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: true,
        });

        wrapper.findComponent('a.modal-close-visible').trigger('click');
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "close" when the function closeModal() is called', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: true,
        });

        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('set size attribute when props size is present', async () => {
        await wrapper.setProps({
            visible: true,
            noClose: false,
            withoutPadding: true,
            size: 'xl',
        });

        expect(wrapper.findComponent('b-modal-stub').attributes('size')).toBe('xl');
    });
});
