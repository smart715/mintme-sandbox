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
        expect(wrapper.find('b-modal').attributes('visible')).to.be.undefined;
    });

    it('should be visible when visible props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('visible')).to.exist;
    });

    it('enable closing on ESC and enable closing on backdrop click when noClose props is false', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('no-close-on-backdrop')).to.be.undefined;
        expect(wrapper.find('b-modal').attributes('no-close-on-esc')).to.be.undefined;
    });

    it('disable closing on ESC and disable closing on backdrop click when noClose props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: true,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('no-close-on-backdrop')).to.exist;
        expect(wrapper.find('b-modal').attributes('no-close-on-esc')).to.exist;
    });

    it('a padding should be present when withoutPadding props is false', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('body-class')).to.equal('');
        expect(wrapper.find('.modal-body').attributes('class')).to.equal('modal-body');
    });

    it('a padding should be absent when withoutPadding props is true', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: true,
           },
        });
        expect(wrapper.find('b-modal').attributes('body-class')).to.equal('m-0 p-0');
        expect(wrapper.find('.modal-body').attributes('class')).to.equal('modal-body m-0 p-0');
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
        expect(wrapper.emitted('close').length).to.be.equal(1);
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
        expect(wrapper.emitted('close').length).to.be.equal(1);
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
        expect(wrapper.find('b-modal').attributes('size')).to.equal('xl');
    });
});
