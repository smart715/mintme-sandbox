import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {shallowMount} from '@vue/test-utils';
import TokenChangeName from '../../js/components/token/TokenChangeName';
import axios from 'axios';
import moxios from 'moxios';
import {MInput} from '../../js/components/UI';

Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenChangeName', () => {
    TokenChangeName.methods.noBadWordsValidator = jest.fn();

    beforeEach(() => {
        moxios.install(axios);
    });

    afterEach(() => {
        moxios.uninstall(axios);
    });

    it('renders correctly with assigned props', async () => {
        const wrapper = shallowMount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
            mocks: {$t: (val) => val, $v: () => jest.fn()},
        });

        const deployedErrorMessage = 'token.change_name.cant_be_changed';
        const exchangedErrorMessage = 'token.change_name.must_own_all';
        expect(wrapper.vm.currentName).toBe('foobar');
        expect(wrapper.vm.newName).toBe('foobar');
        expect(wrapper.findComponent(MInput).props().value).toBe('foobar');

        await wrapper.setProps({isTokenExchanged: true});
        await wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.findComponent(MInput).props().hint).toBe(deployedErrorMessage);

        await wrapper.setProps({isTokenExchanged: true});
        await wrapper.setProps({isTokenNotDeployed: true});
        expect(wrapper.findComponent(MInput).props().hint).toBe(exchangedErrorMessage);

        await wrapper.setProps({isTokenExchanged: false});
        await wrapper.setProps({isTokenNotDeployed: false});
        expect(wrapper.findComponent(MInput).props().hint).toBe(deployedErrorMessage);

        await wrapper.setProps({isTokenExchanged: false});
        await wrapper.setProps({isTokenNotDeployed: true});
        wrapper.vm.newName = 'different';
        wrapper.vm.tokenNameExists = false;
        wrapper.vm.tokenNameProcessing = false;
        wrapper.vm.submitting = false;
        expect(wrapper.findComponent(MInput).props().hint).toBe(null);

        moxios.stubRequest('check_token_name_exists', {
            exists: false,
        });
        moxios.wait(() => {
            expect(wrapper.vm.tokenNameExists).toBe(false);
        });
    });

    describe('throw error', () => {
        it('when token name has spaces in the beginning', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });

            wrapper.findComponent(MInput).vm.$emit('input', '  newName');
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validFirstChars).toBe(true);
        });

        it('when token name has dashes in the beginning', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });
            wrapper.findComponent(MInput).vm.$emit('input', '----newName');
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.$v.newName.validFirstChars).toBe(true);
        });

        it('when token name has spaces in the end', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });
            wrapper.findComponent(MInput).vm.$emit('input', 'newName  ');
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validLastChars).toBe(true);
        });

        it('when token name has dashes in the end', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });
            wrapper.findComponent(MInput).vm.$emit('input', 'newName----');
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.$v.newName.validLastChars).toBe(true);
        });

        it('when token name has spaces between dashes', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });
            wrapper.findComponent(MInput).vm.$emit('input', 'new--- ---Name');
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.noSpaceBetweenDashes).toBe(true);
        });

        it('when token name has chars outside of alphabet, numbers, - and spaces', () => {
            const wrapper = shallowMount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
                mocks: {$t: jest.fn()},
            });
            wrapper.findComponent(MInput).vm.$emit('input', 'new$Name!');
            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newName.validChars).toBe(true);
        });
    });
});
