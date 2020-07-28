import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {createLocalVue, mount} from '@vue/test-utils';
import TokenChangeName from '../../js/components/token/TokenChangeName';
import Axios from '../../js/axios';
import axios from 'axios';
import moxios from 'moxios';
Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenChangeName', () => {
    beforeEach(() => {
        moxios.install(axios);
    });
    afterEach(() => {
        moxios.uninstall(axios);
    });
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
        });
        const deployedErrorMessage = 'The name of a deployed token can\'t be changed';
        const exchangedErrorMessage = 'You must own all your tokens in order to change the token\'s name';
        expect(wrapper.vm.currentName).toBe('foobar');
        expect(wrapper.vm.newName).toBe('foobar');
        expect(wrapper.find('input').element.value).toBe('foobar');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');
        expect(wrapper.contains('#error-message')).toBe(true);
        expect(wrapper.find('#error-message').text()).toBe(deployedErrorMessage);

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');
        expect(wrapper.contains('#error-message')).toBe(true);
        expect(wrapper.find('#error-message').text()).toBe(exchangedErrorMessage);

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');
        expect(wrapper.contains('#error-message')).toBe(true);
        expect(wrapper.find('#error-message').text()).toBe(deployedErrorMessage);

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = true;
        wrapper.vm.$v.hasNotBlockedWords = false;
        wrapper.vm.newName = 'different';
        wrapper.vm.tokenNameExists = false;
        wrapper.vm.tokenNameProcessing = false;
        wrapper.vm.submitting = false;
        expect(wrapper.find('button').attributes('disabled')).toBe(undefined);
        expect(wrapper.contains('#error-message')).toBe(false);

        moxios.stubRequest('check_token_name_exists', {
            exists: false,
        });
        moxios.wait(() => {
            expect(wrapper.vm.tokenNameExists).toBe(false);
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
        expect(wrapper.vm.showTwoFactorModal).toBe(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).toBe(true);
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
        expect(wrapper.vm.showTwoFactorModal).toBe(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).toBe(false);
    });

    describe('throw error', () => {
        it('when token name has spaces in the beginning', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('  newName');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validFirstChars).toBe(true);
        });

        it('when token name has dashes in the beginning', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('----newName');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validFirstChars).toBe(true);
        });

        it('when token name has spaces in the end', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('newName  ');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validLastChars).toBe(true);
        });

        it('when token name has dashes in the end', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('newName----');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validLastChars).toBe(true);
        });

        it('when token name has spaces between dashes', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new--- ---Name');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.noSpaceBetweenDashes).toBe(true);
        });

        it('when token name has chars outside of alphabet, numbers, - and spaces', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new$Name!');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validChars).toBe(true);
        });
    });
});
