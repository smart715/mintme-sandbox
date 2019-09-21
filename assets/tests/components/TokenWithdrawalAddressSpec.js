import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {mount} from '@vue/test-utils';
import TokenWithdrawalAddress from '../../js/components/token/TokenWithdrawalAddress';
Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenWithdrawalAddress', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                twofa: false,
                isTokenExchanged: true,
            },
        });
        expect(wrapper.vm.currentAddress).to.equal('foobar');
        expect(wrapper.vm.newAddress).to.equal('foobar');
        expect(wrapper.find('input').element.value).to.equal('foobar');
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');
    });

    it('renders correctly with assigned props 2', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {isTokenExchanged: false},
        });
        expect(wrapper.find('button').attributes('disabled')).to.equal(undefined);
    });

    it('open TwoFactorModal for saving name when 2fa is enabled', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                twofa: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue('0x1111111111111111111111111111111111111111');
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });
});
