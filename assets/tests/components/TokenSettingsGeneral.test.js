import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenSettingsGeneral from '../../js/components/token_settings/TokenSettingsGeneral';
import moxios from 'moxios';
import '../__mocks__/ResizeObserver';
import axios from 'axios';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';


/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

const propsForTestCorrectlyRenders = {
    currentDescription: 'a'.repeat(200),
    twofaEnabled: false,
    coverImage: '',
    isCreatedOnMintmeSite: true,
};
const localVue = mockVue();
localVue.use(Vuex);
localVue.use(Vuelidate);

const store = new Vuex.Store({
    modules: {
        tokenInfo: {
            namespaced: true,
            getters: {
                getDeploymentStatus: function() {
                    return 'deployed';
                },
            },
        },
        tokenSettings: {
            namespaced: true,
            getters: {
                getTokenName: function() {
                    return 'TEST';
                },
                getIsTokenExchanged: function() {
                    return true;
                },
            },
            mutations: {
                setTokenName: jest.fn(),
            },
        },
    },
});

/**
 * @param {object} propsData
 * @return {Wrapper<Vue>}
 */
function mockTokenSettingsGeneral(propsData = {}) {
    return shallowMount(TokenSettingsGeneral, {
        store,
        localVue,
        propsData: {
            ...propsData,
        },
    });
}

describe('TokenSettingsGeneral', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    Object.defineProperty(window, 'location', {
        value: {
            href: '/',
        },
        configurable: true,
        writable: true,
    });

    describe('currentDescriptionHtmlDecode', () => {
        it('correctly decodes HTML entities in currentDescription', () => {
            const currentDescription = '&lt;p&gt;This is a &lt;em&gt;sample&lt;/em&gt; description&lt;/p&gt;';

            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                currentDescription,
            });

            const decodedDescription = wrapper.vm.currentDescriptionHtmlDecode;

            expect(decodedDescription).toBe('<p>This is a <em>sample</em> description</p>');
        });
    });

    describe('saveSuccess', () => {
        it('redirect to token page on saveSuccess', () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            wrapper.vm.onSaveSuccess();

            expect(window.location).toEqual('token_show_intro');
        });
    });

    describe('isShortDescription', () => {
        it('validates currentDescription length correctly', async () => {
            const currentDescription = 'Short description';
            const descriptionMinLength = 50;

            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                currentDescription,
            });

            await wrapper.setData({descriptionMinLength});
            expect(wrapper.vm.isShortDescription).toBe(true);

            await wrapper.setProps({currentDescription: 'Exactly 50 characters description.................'});
            expect(wrapper.vm.isShortDescription).toBe(false);

            await wrapper.setProps({currentDescription: 'A longer description with more than 50 characters......'});
            expect(wrapper.vm.isShortDescription).toBe(false);
        });
    });

    describe('onTokenNameChange', () => {
        it('updates newTokenName when onTokenNameChange is called', () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            const newName = 'New Token Name';
            wrapper.vm.onTokenNameChange(newName);

            expect(wrapper.vm.newTokenName).toBe(newName);
        });
    });

    describe('onTokenNameValidation', () => {
        it('updates tokenNameInvalid when onTokenNameValidation is called', () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            const validationStatus = true;
            wrapper.vm.onTokenNameValidation(validationStatus);

            expect(wrapper.vm.tokenNameInvalid).toBe(validationStatus);
        });
    });

    describe('save', () => {
        it('shows two-factor modal when twofaEnabled is true', async () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                twofaEnabled: true,
            });
            const sendSaveRequest = jest.spyOn(wrapper.vm, 'sendSaveRequest');

            await wrapper.setData({newTokenName: 'New Token Name'});

            wrapper.vm.save();

            expect(wrapper.vm.showTwoFactorModal).toBe(true);
            expect(sendSaveRequest).not.toHaveBeenCalled();
        });

        it('sends save request when twofaEnabled is false', async () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                twofaEnabled: false,
            });
            const sendSaveRequest = jest.spyOn(wrapper.vm, 'sendSaveRequest');

            await wrapper.setData({newTokenName: 'New Token Name'});

            wrapper.vm.save();

            expect(wrapper.vm.showTwoFactorModal).toBe(false);
            expect(sendSaveRequest).toHaveBeenCalled();
        });
    });

    describe('sendSaveRequest', () => {
        it('sends save request and handles success', async (done) => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                currentDescription: 'Current Description',
                tokenProposalMinAmount: 100,
                dmMinAmount: 50,
                commentMinAmount: 10,
            });
            const onSaveSuccess = jest.spyOn(wrapper.vm, 'onSaveSuccess');

            await wrapper.setData({
                newTokenName: 'New Token Name',
            });

            moxios.stubRequest('token_update', {
                status: 200,
                response: {
                    newDescription: 'New Description',
                },
            });

            await wrapper.vm.sendSaveRequest('code');

            moxios.wait(() => {
                expect(wrapper.vm.newDescription).toBe('New Description');
                expect(onSaveSuccess).toHaveBeenCalled();
                done();
            });
        });

        it('sends save request and handles error', async (done) => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            moxios.stubRequest('token_update', {
                status: 500,
                response: {
                    message: 'Server Error',
                },
            });

            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');
            await wrapper.vm.sendSaveRequest();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('closeTwoFactorModal', () => {
        it('should set showTwoFactorModal to false', async () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            await wrapper.setData({showTwoFactorModal: true});

            await wrapper.vm.closeTwoFactorModal();

            expect(wrapper.vm.showTwoFactorModal).toBe(false);
        });
    });

    describe('checkIfHasChanges', () => {
        it('check if there are changes correctly', () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });

            expect(wrapper.vm.checkIfHasChanges()).toBe(false);

            wrapper.setData({
                newDescription: 'New Description',
                newTokenName: 'New Token Name',
                newTokenProposalMinAmount: 100,
                newDmMinAmount: 50,
                newCommentMinAmount: 10,
            });

            expect(wrapper.vm.checkIfHasChanges()).toBe(true);
        });
    });

    describe('onSaveChanges', () => {
        it('should call save method if there are changes in the form', async () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
            });
            const save = jest.spyOn(wrapper.vm, 'save');

            await wrapper.setData({
                newDescription: 'New Description',
                newTokenName: 'New Token Name',
                newTokenProposalMinAmount: 100,
                newDmMinAmount: 50,
                newCommentMinAmount: 10,
            });

            wrapper.vm.onSaveChanges();

            expect(save).toHaveBeenCalled();
        });

        it('should not call save method if there are no changes in the form', async () => {
            const wrapper = mockTokenSettingsGeneral({
                ...propsForTestCorrectlyRenders,
                tokenProposalMinAmount: 0,
                dmMinAmount: 0,
                commentMinAmount: 0,
            });
            const save = jest.spyOn(wrapper.vm, 'save');

            await wrapper.setData({
                newDescription: 'a'.repeat(200),
                newTokenName: 'TEST',
                newTokenProposalMinAmount: 0,
                newDmMinAmount: 0,
                newCommentMinAmount: 0,
            });

            wrapper.vm.onSaveChanges();
            expect(save).not.toHaveBeenCalled();
        });
    });
});
