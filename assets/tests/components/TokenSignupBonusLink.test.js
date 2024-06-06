import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSignupBonusLink from '../../js/components/token/TokenSignupBonusLink.vue';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';
import Vuelidate from 'vuelidate';
import {generateCoinAvatarHtml} from '../../js/utils';
import {HTTP_ACCESS_DENIED, HTTP_NOT_FOUND, HTTP_CREATED} from '../../js/utils/constants';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: jest.fn((val) => val)};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: jest.fn()};
        },
    });
    return localVue;
}

const signupBonusParams = {
    min_tokens_amount: 0.01,
    min_participants_amount: 100,
    max_participants_amount: 999999,
    min_token_reward: 0.0001,
};

/**
 * @return {Wrapper<vue>}
 * @param {object} options
 */
function mockDefaultWrapper(options = {}) {
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tradeBalance: {
                ...tradeBalance,
                state: {
                    balances: {},
                },
            },
        },
    });

    return shallowMount(TokenSignupBonusLink, {
        localVue,
        store,
        directives: {
            'b-tooltip': {},
        },
        propsData: {
            tokenName: 'TokenNameTest',
            tokenAvatar: 'TokenAvatarTest',
            signupBonusParams,
        },
        ...options,
    });
}

/**
 * @param {Wrapper} wrapper
 * @param {number|null} amount
 */
function setBalance(wrapper, amount = null) {
    wrapper.vm.$store.commit('tradeBalance/setBalances', {
        [wrapper.vm.tokenName]: {available: amount ? amount.toString() : null},
    });
}

describe('TokenSignupBonusLink', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that the spinner is shown and hidden', async () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.findComponent('.loading-spinner').exists()).toBe(false);

        await wrapper.setData({loading: true});
        expect(wrapper.findComponent('.loading-spinner').exists()).toBe(true);
    });

    it('should compute balanceLoaded correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.$store.commit('tradeBalance/setBalances', null);
        expect(wrapper.vm.balanceLoaded).toBe(false);

        setBalance(wrapper, 10);
        expect(wrapper.vm.balanceLoaded).toBe(true);
    });

    it('should compute tokenBalance correctly', () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.tokenBalance).toBe('0');

        setBalance(wrapper, 10);
        expect(wrapper.vm.tokenBalance).toBe('10');
    });

    describe('Check that "btnDisabled" works correctly with different values', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper();
            wrapper.vm.$store.commit('tradeBalance/setBalances', null);
        });

        it('Verify with balance of "0"', () => {
            setBalance(wrapper, 0);

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" and "participantsAmount" of "null" and balance of "0"', async () => {
            setBalance(wrapper, 0);

            await wrapper.setData({
                tokensAmount: null,
                participantsAmount: null,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "min", "participantsAmount" of "null" and balance of "900"', async () => {
            setBalance(wrapper, 900);

            await wrapper.setData({
                tokensAmount: signupBonusParams.min_tokens_amount,
                participantsAmount: null,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "null", "participantsAmount" of "min" and balance of "900"', async () => {
            setBalance(wrapper, 900);

            await wrapper.setData({
                tokensAmount: null,
                participantsAmount: signupBonusParams.min_participants_amount,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "min", "participantsAmount" of "min - 1" and balance of "900"', async () => {
            setBalance(wrapper, 900);

            await wrapper.setData({
                tokensAmount: signupBonusParams.min_tokens_amount,
                participantsAmount: signupBonusParams.min_participants_amount - 1,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "min", "participantsAmount" of "max + 1" and balance of "900"', async () => {
            setBalance(wrapper, 900);

            await wrapper.setData({
                tokensAmount: signupBonusParams.min_tokens_amount,
                participantsAmount: signupBonusParams.max_participants_amount + 1,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "min", "participantsAmount" of "max" and balance of "0.01"', async () => {
            setBalance(wrapper, 0.01);

            await wrapper.setData({
                tokensAmount: signupBonusParams.min_tokens_amount,
                participantsAmount: signupBonusParams.max_participants_amount,
            });

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('Verify with "tokensAmount" of "min", "participantsAmount" of "min" and balance of "900"', async () => {
            setBalance(wrapper, 900);

            await wrapper.setData({
                tokensAmount: signupBonusParams.min_tokens_amount,
                participantsAmount: signupBonusParams.min_participants_amount,
            });

            expect(wrapper.vm.btnDisabled).toBe(false);
        });
    });

    it('should compute insufficientBalance correctly', async () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.$store.commit('tradeBalance/setBalances', null);
        expect(wrapper.vm.insufficientBalance).toBe(false);

        setBalance(wrapper, null);
        expect(wrapper.vm.insufficientBalance).toBe(true);

        setBalance(wrapper, 10);
        await wrapper.setData({tokensAmount: 12});
        expect(wrapper.vm.insufficientBalance).toBe(true);

        setBalance(wrapper, 0.01);
        await wrapper.setData({tokensAmount: 0.01});
        expect(wrapper.vm.insufficientBalance).toBe(false);

        setBalance(wrapper, 0.0101);
        await wrapper.setData({tokensAmount: 0.01});
        expect(wrapper.vm.insufficientBalance).toBe(false);
    });

    describe('Check that "isTokenAmountValid" works correctly with different values', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper();
            wrapper.vm.$store.commit('tradeBalance/setBalances', null);
        });

        it('Verify no amount', () => {
            expect(wrapper.vm.isTokenAmountValid).toBe(false);
        });

        it('Verify with balance of "10" and amount of "0.01"', async () => {
            setBalance(wrapper, 10);

            await wrapper.setData({
                tokensAmount: 0.01,
            });

            expect(wrapper.vm.isTokenAmountValid).toBe(false);
        });

        it('Verify with balance of "0" and amount of "0.01"', async () => {
            setBalance(wrapper, 0);
            await wrapper.setData({
                tokensAmount: 0.01,
            });

            expect(wrapper.vm.isTokenAmountValid).toBe(true);
        });

        it('Verify with balance of "10" and amount of "0"', async () => {
            setBalance(wrapper, 10);
            await wrapper.setData({
                tokensAmount: 0,
            });

            expect(wrapper.vm.isTokenAmountValid).toBe(true);
        });
    });

    it('should return correct value for minTokensAmount', async () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.minTokensAmount).toBe(signupBonusParams.min_tokens_amount);

        await wrapper.setProps({signupBonusParams: {}});
        expect(wrapper.vm.minTokensAmount).toBe(0);
    });

    it('should return correct value for minParticipantsAmount', async () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.minParticipantsAmount).toBe(signupBonusParams.min_participants_amount);

        await wrapper.setProps({signupBonusParams: {}});
        expect(wrapper.vm.minParticipantsAmount).toBe(0);
    });

    it('should return correct value for maxParticipantsAmount', async () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.maxParticipantsAmount).toBe(signupBonusParams.max_participants_amount);

        await wrapper.setProps({signupBonusParams: {}});
        expect(wrapper.vm.maxParticipantsAmount).toBe(0);
    });

    it('should return correct value for minTokenReward', async () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.minTokenReward).toBe(signupBonusParams.min_token_reward);

        await wrapper.setProps({signupBonusParams: {}});
        expect(wrapper.vm.minTokenReward).toBe(0);
    });

    it('should return correct value for participantsTransContext', () => {
        const wrapper = mockDefaultWrapper();
        const participantsTransContext = {
            minParticipantsAmount: signupBonusParams.min_participants_amount,
            maxParticipantsAmount: signupBonusParams.max_participants_amount,
        };

        expect(wrapper.vm.participantsTransContext).toEqual(participantsTransContext);
    });

    it('should return correct value for bonusUrl', () => {
        const wrapper = mockDefaultWrapper();
        wrapper.vm.$routing.generate.mockReturnValueOnce('/TokenNameTest/signup');

        expect(wrapper.vm.bonusUrl).toEqual(location.origin + '/TokenNameTest/signup');
    });

    it('should return correct value for translationsContext', () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.translationsContext).toEqual({
            tokenName: 'TokenNameT...',
            minTokensAmount: signupBonusParams.min_tokens_amount,
            avatar: generateCoinAvatarHtml({image: 'TokenAvatarTest', isUserToken: true}),
        });
    });

    describe('loadTokenSignupBonus', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('should load token signup bonus and emit bonus-link-changed event', async () => {
            const wrapper = mockDefaultWrapper({
                computed: {
                    bonusUrl() {
                        return 'http://example.com';
                    },
                },
            });
            wrapper.vm.$routing.generate.mockReturnValueOnce('/has_active_token_signup_bonus?tokenName=TokenNameTest');
            moxios.stubRequest('/has_active_token_signup_bonus?tokenName=TokenNameTest', {
                status: 200,
            });

            await wrapper.vm.loadTokenSignupBonus();

            expect(wrapper.vm.loading).toBe(false);
            expect(moxios.requests.mostRecent().config.url)
                .toBe('/has_active_token_signup_bonus?tokenName=TokenNameTest');
            expect(wrapper.vm.hasSignUpBonusLink).toBe(true);
            expect(wrapper.emitted('bonus-link-changed')[0]).toEqual(['http://example.com']);
        });

        it('should log an error when request fails', async () => {
            const wrapper = mockDefaultWrapper({
                data() {
                    return {
                        loading: false,
                        hasSignUpBonusLink: false,
                    };
                },
                computed: {
                    bonusUrl() {
                        return 'http://example.com';
                    },
                },
            });
            wrapper.vm.$routing.generate.mockReturnValueOnce('/has_active_token_signup_bonus?tokenName=TokenNameTest');
            moxios.stubRequest('/has_active_token_signup_bonus?tokenName=TokenNameTest', {
                status: 500,
            });

            await wrapper.vm.loadTokenSignupBonus();

            expect(wrapper.vm.loading).toBe(false);
            expect(moxios.requests.mostRecent().config.url)
                .toBe('/has_active_token_signup_bonus?tokenName=TokenNameTest');
            expect(wrapper.vm.hasSignUpBonusLink).toBe(false);
            expect(wrapper.emitted('bonus-link-changed')).toBeUndefined();
            expect(wrapper.vm.$logger.error).toHaveBeenCalled();
        });
    });

    describe('createTokenSignupBonusLink', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper({
                data() {
                    return {
                        tokensAmount: 10,
                        participantsAmount: 5,
                        loading: false,
                        hasSignUpBonusLink: false,
                        errorMessage: '',
                        btnDisabledSwitcher: false,
                        insufficientBalanceSwitcher: false,
                    };
                },
                computed: {
                    btnDisabled: {
                        get() {
                            return this.btnDisabledSwitcher;
                        },
                        set(val) {
                            this.btnDisabledSwitcher = val;
                        },
                    },
                    insufficientBalance: {
                        get() {
                            return this.insufficientBalanceSwitcher;
                        },
                        set(val) {
                            this.insufficientBalanceSwitcher = val;
                        },
                    },
                },
                mocks: {
                    notifyError: jest.fn(),
                    notifySuccess: jest.fn(),
                    $v: {
                        tokensAmount: {
                            $anyError: false,
                        },
                    },
                },
            });
            moxios.install();
        });
        afterEach(() => {
            wrapper.destroy();
            moxios.uninstall();
        });

        it('should not call $axios if btnDisabled or insufficientBalance are true', async () => {
            const axiosSpy = jest.spyOn(wrapper.vm.$axios.single, 'post');
            wrapper.vm.btnDisabled = true;

            await wrapper.vm.createTokenSignupBonusLink();

            expect(axiosSpy).not.toHaveBeenCalled();

            wrapper.setData({tokensAmount: 20});
            wrapper.vm.btnDisabled = true;
            wrapper.vm.insufficientBalance = true;

            await wrapper.vm.createTokenSignupBonusLink();

            expect(axiosSpy).not.toHaveBeenCalled();
        });

        it('should notify success if no errors', async (done) => {
            const notifySuccessSpy = jest.spyOn(wrapper.vm, 'notifySuccess');
            moxios.stubRequest('create_token_sign_up_bonus_link', {
                status: 200,
            });

            await wrapper.vm.createTokenSignupBonusLink();

            moxios.wait(() => {
                expect(notifySuccessSpy).toHaveBeenCalled();
                done();
            });
        });

        it('sets errorMessage if there is an error with tokensAmount', async () => {
            wrapper.vm.$v.tokensAmount.$anyError = true;
            await wrapper.setData({
                tokensAmount: 1,
                participantsAmount: 999999,
            });
            const expectedErrorMessage = wrapper.vm.$t('page.token_settings.tab.sign_up.invalid_reward', {
                minTokenReward: signupBonusParams.min_token_reward,
                tokenName: wrapper.vm.tokenName,
            });

            await wrapper.vm.createTokenSignupBonusLink();

            expect(wrapper.vm.errorMessage).toEqual(expectedErrorMessage);
        });

        it('notifies error if HTTP_CREATED is not the same as request status', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('create_token_sign_up_bonus_link', {
                status: HTTP_NOT_FOUND,
                response: {
                    message: 'Not found',
                },
            });

            await wrapper.vm.createTokenSignupBonusLink();

            moxios.wait(() => {
                expect(notifyErrorSpy).toBeCalled();
                done();
            });
        });

        it('should set loading to false in the finally block', (done) => {
            moxios.stubRequest('create_token_sign_up_bonus_link', {
                status: HTTP_CREATED,
            });

            wrapper.vm.createTokenSignupBonusLink();
            moxios.wait(() => {
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });
    });

    describe('deleteTokenSignupBonusLink', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockDefaultWrapper({
                propsData: {
                    tokenName: 'testToken',
                    signupBonusParams,
                },
                mocks: {
                    $emit: jest.fn(),
                },
                data() {
                    return {
                        hasSignUpBonusLink: true,
                        loadingModal: false,
                    };
                },
            });
            moxios.install();
        });
        afterEach(() => {
            wrapper.destroy();
            moxios.uninstall();
        });

        it('should not call $axios.delete when hasSignUpBonusLink is false', async () => {
            const axiosSpy = jest.spyOn(wrapper.vm.$axios.single, 'delete');

            await wrapper.setData({hasSignUpBonusLink: false});
            await wrapper.vm.deleteTokenSignupBonusLink();

            expect(axiosSpy).not.toHaveBeenCalled();
        });


        it(
            `should update hasSignUpBonusLink to false and emit "bonus-link-changed"
            with null when delete is successful`,
            async () => {
                const eventSpy = jest.spyOn(wrapper.vm, '$emit');

                moxios.stubRequest('delete_token_sign_up_bonus_link', {
                    status: 200,
                });

                await wrapper.vm.deleteTokenSignupBonusLink();
                expect(wrapper.vm.hasSignUpBonusLink).toBe(false);
                expect(eventSpy).toHaveBeenCalledWith('bonus-link-changed', null);
                expect(wrapper.vm.loadingModal).toBe(false);
            });

        it('should call notifyError with the correct message when HTTP_ACCESS_DENIED error occurs', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');
            const errorMsg = 'error message';

            moxios.stubRequest('delete_token_sign_up_bonus_link', {
                status: HTTP_ACCESS_DENIED,
                response: {
                    message: errorMsg,
                },
            });

            await wrapper.vm.deleteTokenSignupBonusLink();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalledWith(errorMsg);
                done();
            });
        });

        it('should call notifyError when non-HTTP_ACCESS_DENIED error occurs', async (done) => {
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');
            moxios.stubRequest('delete_token_sign_up_bonus_link', {
                status: HTTP_NOT_FOUND,
            });

            await wrapper.vm.deleteTokenSignupBonusLink();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });
});
