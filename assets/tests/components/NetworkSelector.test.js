import {shallowMount, createLocalVue} from '@vue/test-utils';
import NetworkSelector from '../../js/components/wallet/NetworkSelector.vue';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = () => false;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        selected: '',
        networks: [],
        isOwner: false,
        withdraw: false,
        deposit: false,
        ...props,
    };
};

const btcNetwork = {networkInfo: {symbol: 'BTC'}};
const testNetworks = [btcNetwork, {networkInfo: {symbol: 'ETH'}}, {networkInfo: {symbol: 'WEB'}}];

const tooltipConfigTest = {
    'boundary': 'window',
    'customClass': 'tooltip-custom',
    'html': true,
    'title': 'withdraw_modal.disabled_network',
    'variant': 'light',
};

describe('NetworkSelector', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(NetworkSelector, {
            sync: false,
            localVue: localVue,
            directives: {
                'b-tooltip': {},
            },
            propsData: createSharedTestProps(),
        });
    });

    it('Verify that "tooltipConfig" works correctly', async () => {
        await wrapper.setProps({
            networks: testNetworks,
        });

        expect(wrapper.vm.tooltipConfig(btcNetwork)).toBeUndefined();

        await wrapper.setProps({
            isOwner: true,
        });

        expect(wrapper.vm.tooltipConfig({})).toEqual(tooltipConfigTest);
    });

    it('Verify that "isNetworkAvailable" works correctly', async () => {
        expect(wrapper.vm.isNetworkAvailable(btcNetwork)).toBe(false);

        await wrapper.setProps({
            networks: testNetworks,
        });

        expect(wrapper.vm.isNetworkAvailable(btcNetwork)).toBe(true);
    });
});
