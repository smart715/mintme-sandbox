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
                isTokenDeployed: true,
                twofa: false,
            },
        });
        expect(wrapper.vm.currentAddress).to.equal('foobar');
    });

    it('can be edited if deployed only', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                isTokenDeployed: false,
                twofa: true,
            },
        });
        expect(wrapper.find('input').exists()).to.be.false;
        wrapper.vm.isTokenDeployed = true;
        expect(wrapper.find('input').exists()).to.be.true;
    });

    it('open TwoFactorModal for saving address when 2fa is enabled', () => {
        const wrapper = mount(TokenWithdrawalAddress, {
            propsData: {
                withdrawalAddress: 'foobar',
                isTokenDeployed: false,
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
