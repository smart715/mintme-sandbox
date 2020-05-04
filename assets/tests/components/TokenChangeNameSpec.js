import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {createLocalVue, mount} from '@vue/test-utils';
import TokenChangeName from '../../js/components/token/TokenChangeName';
import Axios from '../../js/axios';
import axios from 'axios';
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

        const deployedErrorMessage = 'The name of a deployed token can\'t be changed';
        const exchangedErrorMessage = 'You must own all your tokens in order to change the token\'s name';

        expect(wrapper.vm.currentName).to.equal('foobar');
        expect(wrapper.vm.newName).to.equal('foobar');
        expect(wrapper.find('input').element.value).to.equal('foobar');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');
        expect(wrapper.contains('#error-message')).to.equal(true);
        expect(wrapper.find('#error-message').text()).to.equal(deployedErrorMessage);

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');
        expect(wrapper.contains('#error-message')).to.equal(true);
        expect(wrapper.find('#error-message').text()).to.equal(exchangedErrorMessage);

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');
        expect(wrapper.contains('#error-message')).to.equal(true);
        expect(wrapper.find('#error-message').text()).to.equal(deployedErrorMessage);

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');
        expect(wrapper.contains('#error-message')).to.equal(false);
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
        expect(wrapper.vm.showTwoFactorModal).to.equal(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).to.equal(true);
    });

    it('do not open TwoFactorModal for saving name when 2fa is disabled', () => {
        const localVue = createLocalVue();
        localVue.use(Axios);
        localVue.use({
            install(Vue, options) {
                Vue.prototype.$axios = {retry: axios, single: axios};
                Vue.prototype.$routing = {generate: (val) => val};
            },
        });
        const wrapper = mount(TokenChangeName, {
            localVue,
            propsData: {
                currentName: 'foobar',
                twofa: false,
                isTokenExchanged: false,
                isTokenNotDeployed: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).to.equal(false);
    });

    describe('throw error', () => {
        it('when token name has spaces in the beginning', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('  newName');
            expect(!wrapper.vm.$v.newName.validFirstChars).to.deep.equal(true);
        });

        it('when token name has dashes in the beginning', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('----newName');
            expect(!wrapper.vm.$v.newName.validFirstChars).to.deep.equal(true);
        });

        it('when token name has spaces in the end', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('newName  ');
            expect(!wrapper.vm.$v.newName.validLastChars).to.deep.equal(true);
        });

        it('when token name has dashes in the end', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('newName----');
            expect(!wrapper.vm.$v.newName.validLastChars).to.deep.equal(true);
        });

        it('when token name has spaces between dashes', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new--- ---Name');
            expect(!wrapper.vm.$v.newName.noSpaceBetweenDashes).to.deep.equal(true);
        });

        it('when token name has chars outside of alphabet, numbers, - and spaces', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new$Name!');
            expect(!wrapper.vm.$v.newName.validChars).to.deep.equal(true);
        });

        it('when new token name is same as old token name and token not deployed or traded', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            const deployedErrorMessage = 'You didn\'t change the token name';
            wrapper.find('input').setValue('foobar');
            wrapper.vm.isTokenExchanged = false;
            wrapper.vm.isTokenNotDeployed = true;
            expect(wrapper.find('.text-danger').find('.text-center').text()).to.equal(deployedErrorMessage);
        });

        it('when new token name is not entered and token not deployed or traded', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            const deployedErrorMessage = 'Token name shouldn\'t be blank';
            wrapper.find('input').setValue('');
            wrapper.vm.isTokenExchanged = false;
            wrapper.vm.isTokenNotDeployed = true;
            expect(wrapper.find('.text-danger').find('.text-center').text()).to.equal(deployedErrorMessage);
        });
    });
});
