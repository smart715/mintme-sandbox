import {mount} from '@vue/test-utils';
import ConfirmModal from '../../js/components/modal/ConfirmModal';

describe('ConfirmModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        expect(wrapper.vm.visible).to.be.true;
    });

    it('emit "close" when the function closeModal() is running', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).to.be.equal(1);
    });

    it('emit "confirm" when clicking on button "Confirm"', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.find('button.btn.btn-primary').trigger('click');
        expect(wrapper.emitted('confirm').length).to.be.equal(1);
    });

    it('emit "confirm" when the function onConfirm() is running', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.vm.onConfirm();
        expect(wrapper.emitted('confirm').length).to.be.equal(1);
    });

    it('emit "cancel" when clicking on span "Cancel"', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.find('span.btn-cancel.pl-3.c-pointer').trigger('click');
        expect(wrapper.emitted('cancel').length).to.be.equal(1);
    });

    it('emit "cancel" when the function onCancel() is running', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        const event = {preventDefault: () => {}};
        wrapper.vm.onCancel(event);
        expect(wrapper.emitted('cancel').length).to.be.equal(1);
    });

    it('start event.preventDefault() function when the function onCancel() is running', () => {
        const wrapper = mount(ConfirmModal, {
            propsData: {visible: true},
        });
        const event = {preventDefault: () => {
                wrapper.vm.$emit('startPreventDefault');
            },
        };
        wrapper.vm.onCancel(event);
        expect(wrapper.emitted('startPreventDefault').length).to.be.equal(1);
    });
});
