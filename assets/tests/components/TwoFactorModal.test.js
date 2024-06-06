import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TwoFactorModal from '../../js/components/modal/TwoFactorModal';
import VerifyCode from '../../js/components/VerifyCode';

const localVue = mockVue();

Vue.use(Vuelidate);
Vue.use(Toasted);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        visible: true,
        twofa: true,
        ...props,
    };
};

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function mockTwoFactorModal(props = {}, data = {}) {
    return shallowMount(TwoFactorModal, {
        localVue: localVue,
        propsData: createSharedTestProps(props),
        data() {
            return {
                code: '',
                ...data,
            };
        },
    });
}

describe('TwoFactorModal', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mockTwoFactorModal({twofa: false});
        const textInput = wrapper.findComponent('input');

        expect(wrapper.vm.visible).toBe(true);
        expect(wrapper.vm.code).toBe('');
        expect(textInput.exists()).toBe(true);
    });

    it('throw required error when value is  not set', () => {
        const wrapper = mockTwoFactorModal({twofa: false});
        const textInput = wrapper.findComponent('input');

        textInput.setValue('');
        wrapper.vm.onVerify();
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.$error).toBe(true);
    });

    it('emit verify if user click on verify button when  value is set', () => {
        const wrapper = mockTwoFactorModal({twofa: false});
        const textInput = wrapper.findComponent('input');

        textInput.setValue('123');
        wrapper.vm.onVerify();
        expect(wrapper.emitted().verify[0]).toEqual(['123']);
    });

    it('clear code when close', async () => {
        const wrapper = mockTwoFactorModal({twofa: false});
        const textInput = wrapper.findComponent('input');

        textInput.setValue('123');
        wrapper.vm.closeModal();

        await wrapper.setProps({
            visible: true,
        });

        expect(textInput.element.value).toBe('');
    });

    it('2fa label when 2fa activated', () => {
        const wrapper = mockTwoFactorModal();
        expect(wrapper.findComponent('label').text()).toBe('2fa_modal.label.2fa');
    });

    it('email label when 2fa not activated', async () => {
        const wrapper = mockTwoFactorModal({
            twofa: false,
        });

        expect(wrapper.findComponent('label').text()).toBe('2fa_modal.label.email');
    });

    it('should show verifyCode component for 2fa', () => {
        const wrapper = mockTwoFactorModal();
        expect(wrapper.findComponent(VerifyCode).exists()).toBe(true);
    });

    it('should confirm code on verifyCode event', async () => {
        const wrapper = mockTwoFactorModal();
        const onVerifyStub = jest.spyOn(wrapper.vm, 'onVerify');

        await wrapper.findComponent(VerifyCode).vm.$emit('code-entered', '123456');
        expect(wrapper.vm.code).toBe('123456');
        expect(onVerifyStub).toHaveBeenCalled();
    });
});
