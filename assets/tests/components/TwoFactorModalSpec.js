import Vue from 'vue';
import {mount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
Vue.use(Vuelidate);
import TwoFactorModal from '../../js/components/modal/TwoFactorModal';

describe('TwoFactorModal', () => {
    const wrapper = mount(TwoFactorModal, {
         propsData: {visible: true},
         data: {code: ''},
    });

   const textInput = wrapper.find('input');

    it('renders correctly with assigned props', () => {
        expect(wrapper.vm.visible).to.equal(true);
        expect(wrapper.vm.code).to.equal('');
        expect(textInput.exists()).to.deep.equal(true);
    });

    it('it can be verified if user input a auth code value', () => {
        textInput.setValue('AX12347');
        expect(wrapper.vm.code).to.equal('AX12347');
    });

    it('it can be verified if user input a auth code value', () => {
        textInput.setValue('ADFGRT');
        expect(wrapper.vm.code).to.equal('ADFGRT');
    });

    it('emit verify if user click on verify button', () => {
        textInput.setValue('123');
        wrapper.vm.onVerify();
        expect(wrapper.emitted().verify[0]).to.deep.equal(['123']);
    });
});
