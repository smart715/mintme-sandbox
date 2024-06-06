import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import ConfirmModal from '../../js/components/modal/ConfirmModal';
import {MButton} from '../../js/components/UI';

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
        wrapper.findComponent(MButton).vm.$emit('click');
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
        wrapper.findComponent('button.btn-cancel.pl-3').trigger('click');
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

    it('should display default header when no titl prop is provided', async () => {
        const wrapper = shallowMount(ConfirmModal);

        expect(wrapper.vm.modalTitle).toBe('confirm_modal.header');
    });

    it('should display custom title', async () => {
        const wrapper = shallowMount(ConfirmModal, {propsData: {title: 'test'}});

        expect(wrapper.vm.modalTitle).toBe('test');
    });

    it('should not display title when noTitle prop is provided', async () => {
        const wrapper = shallowMount(ConfirmModal, {propsData: {noTitle: true}});

        expect(wrapper.vm.modalTitle).toBe('');
    });
});
