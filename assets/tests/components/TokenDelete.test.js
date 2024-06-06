import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TokenDelete from '../../js/components/token/TokenDelete';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';

Vue.use(Vuelidate);
Vue.use(Toasted);
Vue.use(Vuex);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: jest.fn()};
        },
    });
    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 * @param {object} options
 */
function mockDefaultWrapper(options = {}) {
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tokenStatistics: {
                ...tokenStatistics,
                state: {
                    tokenDeleteSoldLimit: 100000,
                    balance: {
                        tokenExchangeAmount: 100000,
                    },
                    stats: {
                        releasePeriod: '-',
                        hourlyRate: '-',
                        releasedAmount: '-',
                        frozenAmount: '-',
                    },
                },
            },
        },
    });

    return shallowMount(TokenDelete, {
        localVue,
        store,
        directives: {
            'b-tooltip': {},
        },
        ...options,
    });
}

describe('TokenDelete', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('renders correctly with different assigned props', () => {
        const wrapper = mockDefaultWrapper({
            data: () => ({
                isTokenOverDeleteLimit: null,
            }),
            propsData: {
                isTokenNotDeployed: false,
            },
        });

        it('when isTokenOverDeleteLimit = null and isTokenNotDeployed = false', () => {
            expect(wrapper.vm.loaded).toBe(false);
            expect(wrapper.vm.btnDisabled).toBe(true);
            expect(wrapper.findComponent('span').classes('text-muted')).toBe(true);
        });

        it('when isTokenOverDeleteLimit = true and isTokenNotDeployed = false', async () => {
            await wrapper.setData({isTokenOverDeleteLimit: true});
            await wrapper.setProps({isTokenNotDeployed: false});

            expect(wrapper.vm.loaded).toBe(true);
            expect(wrapper.vm.btnDisabled).toBe(true);
            expect(wrapper.findComponent('span').classes('text-muted')).toBe(true);
        });

        it('when isTokenOverDeleteLimit = false and isTokenNotDeployed = false', async () => {
            await wrapper.setData({isTokenOverDeleteLimit: false});
            await wrapper.setProps({isTokenNotDeployed: false});

            expect(wrapper.vm.btnDisabled).toBe(true);
            expect(wrapper.findComponent('span').classes('text-muted')).toBe(true);
        });

        it('when isTokenOverDeleteLimit = false and isTokenNotDeployed = true', async () => {
            await wrapper.setProps({isTokenNotDeployed: true});

            expect(wrapper.vm.btnDisabled).toBe(false);
            expect(wrapper.findComponent('span').classes('text-muted')).toBe(false);
        });
    });

    test('fetches data on mount', async (done) => {
        jest.spyOn(console, 'error').mockImplementation();

        moxios.stubRequest('token_over_delete_limit', {
            status: 200,
            response: {
                data: 'test',
            },
        });

        const wrapper = mockDefaultWrapper();

        await wrapper.vm.$nextTick();

        moxios.wait(() => {
            expect(wrapper.vm.isTokenOverDeleteLimit).toEqual({data: 'test'});
            done();
        });
    });

    test('handles error on mount', async (done) => {
        moxios.stubRequest('token_over_delete_limit', {
            status: 500,
        });

        const wrapper = mockDefaultWrapper();

        await wrapper.vm.$nextTick();

        moxios.wait(() => {
            expect(wrapper.vm.serviceUnavailable).toBe(true);
            expect(wrapper.vm.$logger.error).toHaveBeenCalled();
            done();
        });
    });

    describe('closeTwoFactorModal', () => {
        it('Set showTwoFactorModal to false', async () => {
            const wrapper = mockDefaultWrapper();

            await wrapper.vm.closeTwoFactorModal();

            expect(wrapper.vm.showTwoFactorModal).toBe(false);
        });
    });

    describe('deleteToken', () => {
        it('Doesn\'t call sendConfirmCode  if needToSendCode = false', async () => {
            const wrapper = mockDefaultWrapper({
                data: () => ({
                    needToSendCode: false,
                }),
            });

            const sendConfirmCode = jest.spyOn(wrapper.vm, 'sendConfirmCode');

            await wrapper.vm.deleteToken();

            expect(sendConfirmCode).not.toHaveBeenCalled();
        });

        it('Call sendConfirmCode if needToSendCode = true', async () => {
            const wrapper = mockDefaultWrapper({
                data: () => ({
                    needToSendCode: true,
                }),
            });
            const sendConfirmCode = jest.spyOn(wrapper.vm, 'sendConfirmCode');

            await wrapper.vm.deleteToken();

            expect(sendConfirmCode).toHaveBeenCalled();
        });
    });

    describe('doDeleteToken', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper({
                propsData: {
                    isTokenNotDeployed: true,
                },
                data: () => ({
                    isTokenOverDeleteLimit: false,
                }),
            });
        });

        afterEach(() => {
            wrapper.destroy();
        });

        it('Should call notify error if isTokenOverDeleteLimit = true', async () => {
            await wrapper.setData({isTokenOverDeleteLimit: true});
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            await wrapper.vm.doDeleteToken();

            expect(notifyErrorSpy).toHaveBeenCalled();
        });

        it('Should call notify error if isTokenNotDeployed = false', async () => {
            await wrapper.setProps({isTokenNotDeployed: false});
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            await wrapper.vm.doDeleteToken();

            expect(notifyErrorSpy).toHaveBeenCalled();
        });

        it('Should call notifySuccess if axios request is successful and redirect to homepage', async (done) => {
            global.window = Object.create(window);

            Object.defineProperty(window, 'location', {
                value: {
                    href: 'url',
                },
            });

            const notifySuccessSpy = jest.spyOn(wrapper.vm, 'notifySuccess');

            moxios.stubRequest('token_delete', {
                status: 200,
                response: {
                    data: {
                        message: 'OK',
                    },
                },
            });

            await wrapper.vm.doDeleteToken();

            moxios.wait(() => {
                expect(notifySuccessSpy).toHaveBeenCalled();
                expect(window.location.href).toBe('homepage');
                done();
            });
        });

        it(
            `Should call notifyError and sendConfirmCode if axios request is failing and
            has response (2fa code is expired)`,
            async (done) => {
                const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');
                const sendConfirmCode = jest.spyOn(wrapper.vm, 'sendConfirmCode');

                moxios.stubRequest('token_delete', {
                    status: 500,
                    response: {message: '2fa code is expired'},
                });

                await wrapper.vm.doDeleteToken();

                moxios.wait(() => {
                    expect(notifyErrorSpy).toHaveBeenCalled();
                    expect(sendConfirmCode).toHaveBeenCalled();
                    done();
                });
            }
        );

        it('Should call notifyError if there is no message', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('token_delete', {
                status: 500,
                response: {
                    data: {
                        error: 'error',
                    },
                },
            });

            await wrapper.vm.doDeleteToken();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('sendConfirmCode', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper({
                propsData: {
                    isTokenNotDeployed: true,
                },
                data: () => ({
                    isTokenOverDeleteLimit: false,
                }),
            });
        });

        afterEach(() => {
            wrapper.destroy();
        });

        it('Doesn\'t call axios request if btnDisabled = false', async () => {
            const axiosSpy = jest.spyOn(wrapper.vm.$axios.single, 'post');
            await wrapper.setData({isTokenOverDeleteLimit: true});

            await wrapper.vm.sendConfirmCode();

            expect(axiosSpy).not.toHaveBeenCalled();
        });

        it('Should call notifySuccess if request is successful and set needToSendCode to false', async (done) => {
            const notifySuccessSpy = jest.spyOn(wrapper.vm, 'notifySuccess');

            moxios.stubRequest('token_send_code', {
                status: 200,
                response: {
                    data: {
                        message: 'OK',
                    },
                },
            });

            await wrapper.vm.sendConfirmCode();

            moxios.wait(() => {
                expect(notifySuccessSpy).toHaveBeenCalled();
                expect(wrapper.vm.needToSendCode).toBe(false);
                done();
            });
        });

        it('Should call notifyError if axios request is failing and has response message', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('token_send_code', {
                status: 500,
                response: {message: 'error'},
            });

            await wrapper.vm.sendConfirmCode();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });

        it('Should call notifyError if there is no message in response', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('token_send_code', {
                status: 500,
                response: {
                    data: {
                        error: 'error',
                    },
                },
            });

            await wrapper.vm.sendConfirmCode();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });
});
