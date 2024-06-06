import {createLocalVue, shallowMount} from '@vue/test-utils';
import TopHolders from '../../js/components/trade/TopHolders';
import {generateCoinAvatarHtml} from '../../js/utils';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$store = new Vuex.Store({
                modules: {
                    websocket: {
                        namespaced: true,
                        actions: {
                            addMessageHandler: jest.fn(),
                        },
                    },
                },
            });
        },
    });
    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function mockTopHolders() {
    return shallowMount(TopHolders, {
        localVue: mockVue(),
        propsData: {
            tradersProp: [],
            tokenName: 'jasm-token',
            websocketUrl: 'web_socket_url',
            tokenAvatar: 'jasm-token-avatar',
            isMobileScreen: false,
            serviceUnavailable: false,
        },
    });
}

const tradersTest = [
    {
        balance: 22,
        user: {
            profile: {
                nickname: 'jasm',
                image: {
                    avatar_small: 'avatar_small',
                },
            },
        },
    },
];

describe('TopHolders', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should hide the table if there are not traders', () => {
        const wrapper = mockTopHolders();

        expect(wrapper.vm.hasTraders).toBe(false);
        expect(wrapper.findComponent('b-table').exists()).toBe(false);
    });

    describe('Verify that "hasTraders" is working correctly', () => {
        const wrapper = mockTopHolders();

        it('When the length is greater than 0', async () => {
            await wrapper.setData({
                traders: tradersTest,
            });

            expect(wrapper.vm.hasTraders).toBe(true);
        });

        it('When length is less than 0', async () => {
            await wrapper.setData({
                traders: [],
            });

            expect(wrapper.vm.hasTraders).toBe(false);
        });
    });

    it('Verify that "holders" is working correctly', async () => {
        const wrapper = mockTopHolders();
        const result = [{
            amount: 22,
            trader: 'jasm',
            traderAvatar: 'avatar_small',
            url: 'profile-view',
            wreath: null,
        }];

        await wrapper.setData({
            traders: tradersTest,
        });

        expect(wrapper.vm.holders).toStrictEqual(result);
    });

    it('Verify that "holdersToShow" is working correctly', async () => {
        const wrapper = mockTopHolders();
        const tradersTestTwo = tradersTest;

        for (let i = 0; 6 > i; i++) {
            tradersTestTwo.push(tradersTestTwo[0]);
        }

        await wrapper.setData({
            traders: tradersTestTwo,
        });

        expect(wrapper.vm.holdersToShow.length).toBe(5);
    });

    describe('Verify that "shouldFold" is working correctly', () => {
        it('When isMobileScreen = false and isFolded = true', () => {
            const wrapper = mockTopHolders();

            expect(wrapper.vm.shouldFold).toBe(false);
        });

        it('When isMobileScreen = true and isFolded = false', async () => {
            const wrapper = mockTopHolders();

            await wrapper.setProps({
                isMobileScreen: true,
            });

            await wrapper.setData({
                isFolded: false,
            });

            expect(wrapper.vm.shouldFold).toBe(false);
        });

        it('When isMobileScreen = true and isFolded = true', async () => {
            const wrapper = mockTopHolders();

            await wrapper.setProps({
                isMobileScreen: true,
            });

            await wrapper.setData({
                isFolded: true,
            });

            expect(wrapper.vm.shouldFold).toBe(true);
        });
    });

    it('Verify that "translationsContext" is working correctly', () => {
        const wrapper = mockTopHolders();
        const result = {
            tokenAvatar: generateCoinAvatarHtml({image: 'jasm-token-avatar', isUserToken: true}),
            tokenName: 'jasm-token',
        };

        expect(wrapper.vm.translationsContext).toStrictEqual(result);
    });

    it('Verify that "getTraders" is working correctly', (done) => {
        const wrapper = mockTopHolders();

        moxios.stubRequest('top_holders', {
            status: 200,
            response: tradersTest,
        });

        wrapper.vm.getTraders();

        moxios.wait(() => {
            expect(wrapper.vm.traders).toBe(tradersTest);
            done();
        });
    });
});
