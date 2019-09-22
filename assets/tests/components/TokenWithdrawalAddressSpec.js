import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {mount} from '@vue/test-utils';
import TokenWithdrawalAddress from '../../js/components/token/TokenWithdrawalAddress';
Vue.use(Vuelidate);
Vue.use(Toasted);

const newAddress = '0x1111111111111111111111111111111111111111';

describe('TokenWithdrawalAddress', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                twofa: false,
            },
        });
        expect(wrapper.vm.currentAddress).to.equal('foobar');
        expect(wrapper.vm.newAddress).to.equal('foobar');
        expect(wrapper.find('input').element.value).to.equal('foobar');
    });

    it('open TwoFactorModal for saving address when 2fa is enabled', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                twofa: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue(newAddress);
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });

    it('do not open TwoFactorModal for saving address when 2fa is disabled', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue(newAddress);
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
    });
});
