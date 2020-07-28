import {mount} from '@vue/test-utils';
import Modal from '../../js/components/modal/Modal';

describe('Modal', () => {
    it('shouldn\'t be visible when visible props is false', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: false,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('visible')).toBe(undefined);
    });

    it('should be visible when visible props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('visible')).toBe('true');
    });

    it('enable closing on ESC and enable closing on backdrop click when noClose props is false', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('no-close-on-backdrop')).toBe(undefined);
        expect(wrapper.find('b-modal').attributes('no-close-on-esc')).toBe(undefined);
    });

    it('disable closing on ESC and disable closing on backdrop click when noClose props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: true,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('no-close-on-backdrop')).toBe('true');
        expect(wrapper.find('b-modal').attributes('no-close-on-esc')).toBe('true');
    });

    it('a padding should be present when withoutPadding props is false', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('body-class')).toBe('');
        expect(wrapper.find('.modal-body').attributes('class')).toBe('modal-body');
    });

    it('a padding should be absent when withoutPadding props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: true,
           },
        });
        expect(wrapper.find('b-modal').attributes('body-class')).toBe('m-0 p-0');
        expect(wrapper.find('.modal-body').attributes('class')).toBe('modal-body m-0 p-0');
    });

    it('emit "close" when clicking on <a>', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: true,
           },
        });
        wrapper.find('a').trigger('click');
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: true,
           },
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('set size attribute when props size is present', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: true,
                size: 'xl',
           },
        });
        expect(wrapper.find('b-modal').attributes('size')).toBe('xl');
    });
});
