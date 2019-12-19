import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {mount} from '@vue/test-utils';
import TokenReleaseAddress from '../../js/components/token/TokenReleaseAddress';
Vue.use(Vuelidate);
Vue.use(Toasted);

const newAddress = '0x1111111111111111111111111111111111111111';

describe('TokenReleaseAddress', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenReleaseAddress, {
            propsData: {
                releaseAddress: 'foobar',
                isTokenDeployed: true,
                twofa: false,
            },
        });
        expect(wrapper.vm.currentAddress).to.equal('foobar');
    });

    it('can be edited if deployed only', () => {
        const wrapper = mount(TokenReleaseAddress, {
            propsData: {
                releaseAddress: 'foobar',
                isTokenDeployed: false,
                twofa: true,
            },
        });
        expect(wrapper.find('input').exists()).to.be.false;
        wrapper.vm.isTokenDeployed = true;
        expect(wrapper.find('input').exists()).to.be.true;
    });

    describe('2fa modal', () => {
        it('is displayed after submit if 2fa is enabled', () => {
            const wrapper = mount(TokenReleaseAddress, {
                propsData: {
                    releaseAddress: 'foobar',
                    isTokenDeployed: true,
                    twofa: true,
                },
            });
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
            wrapper.find('input').setValue(newAddress);
            wrapper.find('.btn-primary').trigger('click');
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
        });

        it('is not displayed after submit if 2fa is disabled', () => {
            const wrapper = mount(TokenReleaseAddress, {
                propsData: {
                    releaseAddress: 'foobar',
                    isTokenDeployed: true,
                    twofa: false,
                },
            });
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
            wrapper.find('input').setValue(newAddress);
            wrapper.find('.btn-primary').trigger('click');
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        });
    });
});
