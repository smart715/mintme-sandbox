import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {mount} from '@vue/test-utils';
import TokenChangeName from '../../js/components/token/TokenChangeName';
import { exists } from 'fs';
Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenChangeName', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
        });

        expect(wrapper.vm.currentName).to.equal('foobar');
        expect(wrapper.vm.newName).to.equal('foobar');
        expect(wrapper.find('input').element.value).to.equal('foobar');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal(undefined);
    });

    describe('throw error', () => {
        it('when value is not set', (done) => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.$v.$error).to.deep.equal(true);
                done();
            });
        });

        it('when token name has dashes in the beginning', (done) => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('---newName');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.$v.$error).to.deep.equal(true);
                done();
            });
        });

        it('when token name has spaces in the beginning', (done) => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('   newName');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            console.log(wrapper.vm.newName);
            exit();
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.$v.$error).to.deep.equal(true);
                done();
            });
        });

        it('when token name has spaces between dashes', (done) => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new--- ---Name');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.$v.$error).to.deep.equal(true);
                done();
            });
        });
    });

    it('open TwoFactorModal for saving name when 2fa is enabled', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: true,
                isTokenExchanged: false,
                isTokenNotDeployed: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue('newName');
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });

    it('do not open TwoFactorModal for saving name when 2fa is disabled', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue('newName');
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
    });
});
