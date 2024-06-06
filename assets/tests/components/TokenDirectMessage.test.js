import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import TokenDirectMessage from '../../js/components/chat/TokenDirectMessage';

const $routing = {generate: (val, params) => val + (params ? params.tokenName : '')};
const $t = (val) => val;

const localVue = mockVue();

/**
 * @return {Component}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = $routing;
            Vue.prototype.$t = $t;
            Vue.prototype.$toasted = {show: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} store
 * @return {Vuex.Store}
 */
function createSharedTestStore(store = {}) {
    return new Vuex.Store({
        modules: {
            tradeBalance: {
                namespaced: true,
                getters: {getQuoteFullBalance: () => 50},
            },
            ...store,
        },
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        ...props,
    };
}

describe('TokenDirectMessage', () => {
    let store;
    let wrapper;

    beforeEach(() => {
        store = createSharedTestStore();
        wrapper = shallowMount(TokenDirectMessage, {
            localVue: localVue,
            store: store,
            propsData: createSharedTestProps(),
            stubs: {
                LoginSignupSwitcher: true,
            },
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('show message button', async () => {
        await wrapper.setProps({
            loggedIn: true,
            isOwner: true,
            dmMinAmount: 100,
            tokenName: 'Foo',
        });

        expect(wrapper.findComponent('button').exists()).toBe(true);
    });

    it('should compute getDirectMessageLink for owner correctly', async () => {
        await wrapper.setProps({
            loggedIn: true,
            isOwner: true,
            dmMinAmount: 100,
            tokenName: 'Foo',
        });

        expect(wrapper.vm.getDirectMessageLink).toBe('chat');
    });

    it('should compute getDirectMessageLink correctly for non-owner if there are not enough tokens', async () => {
        await wrapper.setProps({
            loggedIn: true,
            isOwner: false,
            dmMinAmount: 100,
            tokenName: 'Foo',
        });

        expect(wrapper.vm.getDirectMessageLink).toBe(null);
    });

    it('should return true if getQuoteFullBalance greater than or equal to dmMinAmount', async () => {
        await wrapper.setProps({
            dmMinAmount: 10,
        });

        expect(wrapper.vm.isEnoughUserFunds).toBe(true);
    });

    it('should return false if getQuoteFullBalance less than dmMinAmount', async () => {
        await wrapper.setProps({
            dmMinAmount: 100,
        });

        expect(wrapper.vm.isEnoughUserFunds).toBe(false);
    });

    it('should set showModal correctly when the function checkDirectMessage() is called and loggenIn false',
        async () => {
            await wrapper.setProps({
                showModal: false,
                loggedIn: false,
            });

            const e = {preventDefault: jest.fn()};
            wrapper.vm.checkDirectMessage(e);
            expect(wrapper.vm.showModal).toBe(true);
        }
    );

    it('should call notifyError() when the function checkDirectMessage() is called and loggenIn true',
        async () => {
            const e = {preventDefault: jest.fn()};
            wrapper.vm.notifyError = jest.fn();

            await wrapper.setProps({
                showModal: false,
                loggedIn: true,
            });


            wrapper.vm.checkDirectMessage(e);
            expect(wrapper.vm.notifyError).toHaveBeenCalled();
        }
    );

    it('should set showModal correctly when the function closeModal() is called', async () => {
        await wrapper.setProps({
            showModal: true,
        });

        wrapper.vm.closeModal();
        expect(wrapper.vm.showModal).toBe(false);
    });
});
