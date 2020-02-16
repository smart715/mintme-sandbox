import {mount} from '@vue/test-utils';
import Modal from '../../js/components/modal/Modal';

describe('Modal', () => {
    it('the modal is not visible', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: false,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('visible')).to.be.undefined;
    });

    it('the modal is visible', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: false,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('visible')).to.equal('true');
    });

    it('disable closing on ESC and disable closing on backdrop click', () => {
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

    it('enable closing on ESC and enable closing on backdrop click', () => {
        const wrapper = mount(Modal, {
           propsData: {
                visible: true,
                noClose: true,
                withoutPadding: false,
           },
        });
        expect(wrapper.find('b-modal').attributes('no-close-on-backdrop')).to.equal('true');
        expect(wrapper.find('b-modal').attributes('no-close-on-esc')).to.equal('true');
    });

    it('padding present', () => {
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

    it('without padding', () => {
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

    it('emit "close" when you click on <a>', () => {
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

    it('emit "close"', () => {
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

    it('set modal size', () => {
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
