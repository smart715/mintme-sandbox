import {createLocalVue, shallowMount} from '@vue/test-utils';
import CoinNetworkSelector from '../../js/components/wallet/CoinNetworkSelector';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
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
        networkObjects: [{symbol: 'WEB', networkName: 'Mintme'}, {symbol: 'BTC', networkName: 'Bitcoin'}],
        ...props,
    };
}

const networkWEB = {symbol: 'WEB', networkName: 'Mintme'};

describe('CoinNetworkSelector', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CoinNetworkSelector, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    it('Verify that "handleSelect" works correctly', () => {
        wrapper.vm.handleSelect(networkWEB);

        expect(wrapper.emitted().input).toBeTruthy();
        expect(wrapper.emitted().input[0]).toEqual([networkWEB]);

        expect(wrapper.emitted().select).toBeTruthy();
        expect(wrapper.emitted().select[0]).toEqual([networkWEB]);
    });
});
