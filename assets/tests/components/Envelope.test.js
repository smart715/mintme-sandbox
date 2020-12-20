import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import Envelope from '../../js/components/chat/Envelope';

const $routing = {generate: (val, params) => val + (params ? params.tokenName : '')};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = $routing;
        },
    });
    return localVue;
}

describe('Envelope', () => {
    it('dont show envelope icon', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            propsData: {
                loggedIn: false,
                isOwner: false,
                dmMinAmount: 100,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.find('a').exists()).toBe(false);
    });

    it('show envelope icon', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            propsData: {
                loggedIn: true,
                isOwner: true,
                dmMinAmount: 100,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.find('font-awesome-icon').attributes('icon')).toBe('envelope');
    });

    it('should compute showEnvelope correctly', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            propsData: {
                loggedIn: true,
                isOwner: false,
                dmMinAmount: 100,
                getQuoteBalance: 0,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.vm.showEnvelope).toBe(true);
    });

    it('should compute getDirectMessageLink for owner correctly', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        state: {
                            quoteBalance: 0,
                        },
                        getters: {
                            getQuoteBalance: function(state) {
                                return state.quoteBalance;
                            },
                        },
                    },
                },
            }),
            propsData: {
                loggedIn: true,
                isOwner: true,
                dmMinAmount: 100,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.vm.getDirectMessageLink).toBe('chat');
    });

    it('should compute getDirectMessageLink correctly for non-owner with enough tokens', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        state: {
                            quoteBalance: 101,
                        },
                        getters: {
                            getQuoteBalance: function(state) {
                                return state.quoteBalance;
                            },
                        },
                    },
                },
            }),
            propsData: {
                loggedIn: true,
                isOwner: false,
                dmMinAmount: 100,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.vm.getDirectMessageLink).toBe('chatFoo');
    });

    it('should compute getDirectMessageLink correctly for non-owner if there are not enough tokens', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        state: {
                            quoteBalance: 99,
                        },
                        getters: {
                            getQuoteBalance: function(state) {
                                return state.quoteBalance;
                            },
                        },
                    },
                },
            }),
            propsData: {
                loggedIn: true,
                isOwner: false,
                dmMinAmount: 100,
                tokenName: 'Foo',
            },
        });

        expect(wrapper.vm.getDirectMessageLink).toBe(null);
    });
});
