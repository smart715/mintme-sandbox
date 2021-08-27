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
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        getters: {getQuoteBalance: () => {}},
                    },
                },
            }),
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
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        getters: {getQuoteBalance: () => {}},
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

        expect(wrapper.find('font-awesome-icon-stub').attributes('icon')).toBe('envelope');
    });

    it('should compute showEnvelope correctly', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        getters: {getQuoteBalance: () => {}},
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

        expect(wrapper.vm.showEnvelope).toBe(true);
    });

    it('should compute getDirectMessageLink for owner correctly', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        getters: {getQuoteBalance: () => {}},
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

    it('should compute getDirectMessageLink correctly for non-owner if there are not enough tokens', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            store: new Vuex.Store({
                modules: {
                    tradeBalance: {
                        getters: {getQuoteBalance: () => {}},
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
