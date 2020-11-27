import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import ConfirmModal from '../../js/components/modal/ConfirmModal';

Vue.use({
    install(Vue, options) {
        Vue.prototype.$t = (val) => val;
    },
});


describe('ConfirmModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        expect(wrapper.vm.visible).toBe(true);
    });

    it('emit "close" when the function closeModal() is called', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('emit "confirm" when clicking on button "Confirm"', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.find('button.btn.btn-primary').trigger('click');
        expect(wrapper.emitted('confirm').length).toBe(1);
    });

    it('emit "confirm" when the function onConfirm() is called', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.vm.onConfirm();
        expect(wrapper.emitted('confirm').length).toBe(1);
    });

    it('emit "cancel" when clicking on button "Cancel"', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        wrapper.find('button.btn-cancel.pl-3').trigger('click');
        expect(wrapper.emitted('cancel').length).toBe(1);
    });

    it('emit "cancel" when the function onCancel() is called', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        const event = {preventDefault: () => {}};
        wrapper.vm.onCancel(event);
        expect(wrapper.emitted('cancel').length).toBe(1);
    });

    it('start event.preventDefault() function when the function onCancel() is called', () => {
        const wrapper = shallowMount(ConfirmModal, {
            propsData: {visible: true},
        });
        const event = {preventDefault: () => {
                wrapper.vm.$emit('startPreventDefault');
            },
        };
        wrapper.vm.onCancel(event);
        expect(wrapper.emitted('startPreventDefault').length).toBe(1);
    });
});
