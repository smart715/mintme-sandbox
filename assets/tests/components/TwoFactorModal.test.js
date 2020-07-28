import Vue from 'vue';
import {mount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
Vue.use(Vuelidate);
Vue.use(Toasted);
import TwoFactorModal from '../../js/components/modal/TwoFactorModal';

describe('TwoFactorModal', () => {
    const wrapper = mount(TwoFactorModal, {
         propsData: {visible: true},
         data: {code: ''},
        mocks: {
            $toasted: {show: () => {}},
        },
    });

    const textInput = wrapper.find('input');

    it('renders correctly with assigned props', () => {
        expect(wrapper.vm.visible).toBe(true);
        expect(wrapper.vm.code).toBe('');
        expect(textInput.exists()).toBe(true);
    });

    it('throw required error when value is  not set', () => {
        textInput.setValue('');
        wrapper.vm.onVerify();
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.$error).toBe(true);
    });

    it('emit verify if user click on verify button when  value is set', () => {
        textInput.setValue('123');
        wrapper.vm.onVerify();
        expect(wrapper.emitted().verify[0]).toEqual(['123']);
    });

    it('clear code when close', () => {
        textInput.setValue('123');
        wrapper.vm.closeModal();
        wrapper.vm.visible = true;
        expect(textInput.element.value).toBe('');
    });

    it('2fa label when 2fa activated', () => {
        const wrapper = mount(TwoFactorModal, {
             propsData: {twofa: true},
        });
        expect(wrapper.find('label').text()).toBe('Two Factor Authentication Code:');
    });

    it('email label when 2fa not activated', () => {
        const wrapper = mount(TwoFactorModal, {
             propsData: {twofa: false},
        });
        expect(wrapper.find('label').text()).toBe('Email Verification Code:');
    });
});
