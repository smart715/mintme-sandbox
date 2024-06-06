import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSettingsHeader from '../../js/components/token_settings/TokenSettingsHeader.vue';
import Vuex from 'vuex';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

/**
 * @return {Vuex.Store}
 */
function createSharedTestStore() {
    return new Vuex.Store({
        modules: {
            tokenSettings: {
                namespaced: true,
                getters: {
                    getTokenName: () => 'jasmToken',
                },
            },
        },
    });
};

const tokenName = 'ETH';
const tokenAvatar = 'https://example.com/eth.png';

describe('TokenSettingsHeader', () => {
    let wrapper;
    let store;

    beforeEach(() => {
        store = createSharedTestStore();

        wrapper = shallowMount(TokenSettingsHeader, {
            localVue: localVue,
            store: store,
            propsData: {
                tokenName,
                tokenAvatar,
            },
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('should show token dropdown when there are multiple tokens', async () => {
        await wrapper.setProps({
            tokens: [],
            tokensCount: 2,
        });

        expect(wrapper.findComponent('.token-dropdown-menu').exists()).toBe(true);
    });

    it('does not display the token dropdown if there is only 1 token', async () => {
        await wrapper.setProps({tokensCount: 1});

        expect(wrapper.findComponent('.token-dropdown-menu').exists()).toBe(false);
    });

    it('should return a tooltip config object with title, placement, and disabled properties', () => {
        const tokenName = 'MyToken';
        const tooltipConfig = wrapper.vm.tooltipConfig(tokenName);

        expect(tooltipConfig.title).toBe(tokenName);
        expect(tooltipConfig.placement).toBe('right');
        expect(tooltipConfig.disabled).toBe(true);
    });

    describe('Check that "tokenSettingsLink" works correctly', () => {
        it('When the token name matches', () => {
            expect(wrapper.vm.tokenSettingsLink('jasmToken')).toBeUndefined();
        });

        it('When the token name does not match', () => {
            expect(wrapper.vm.tokenSettingsLink('tendoTheBest')).toBe('token_settings');
        });
    });
});
