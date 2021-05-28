import {shallowMount, createLocalVue} from '@vue/test-utils';
import Modal from '../../js/components/modal/Modal';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    return localVue;
}

describe('Modal', () => {
    it('shouldn\'t be visible when visible props is false', () => {
        const wrapper = shallowMount(Modal, {
            propsData: {
               visible: false,
               noClose: false,
               withoutPadding: false,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('visible')).toBe(undefined);
    });

    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: false,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('visible')).toBe('true');
    });

    it('enable closing on ESC and enable closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: false,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('nocloseonbackdrop')).toBe(undefined);
        expect(wrapper.find('b-modal-stub').attributes('nocloseonesc')).toBe(undefined);
    });

    it('disable closing on ESC and disable closing on backdrop click when noClose props is true', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: true,
               withoutPadding: false,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('nocloseonbackdrop')).toBe('true');
        expect(wrapper.find('b-modal-stub').attributes('nocloseonesc')).toBe('true');
    });

    it('a padding should be present when withoutPadding props is false', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: false,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('bodyclass')).toBe('');
        expect(wrapper.find('.modal-body').attributes('class')).toBe('modal-body');
    });

    it('a padding should be absent when withoutPadding props is true', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: true,
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('bodyclass')).toBe('m-0 p-0');
        expect(wrapper.find('.modal-body').attributes('class')).toBe('modal-body m-0 p-0');
    });

    it('emit "close" when clicking on <a>', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: true,
           },
            localVue: mockVue(),
        });
        wrapper.find('a').trigger('click');
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: true,
           },
            localVue: mockVue(),
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('set size attribute when props size is present', () => {
        const wrapper = shallowMount(Modal, {
           propsData: {
               visible: true,
               noClose: false,
               withoutPadding: true,
               size: 'xl',
           },
            localVue: mockVue(),
        });
        expect(wrapper.find('b-modal-stub').attributes('size')).toBe('xl');
    });
});
