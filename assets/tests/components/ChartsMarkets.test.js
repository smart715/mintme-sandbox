import {createLocalVue, shallowMount} from '@vue/test-utils';
import ChartsMarkets from '../../js/components/trading/ChartsMarkets.vue';
import {
    CHART_DEFAULT_DUMMY_DATA,
    CHART_NEGATIVE_DUMMY_DATA,
    CHART_POSITIVE_DUMMY_DATA,
} from '../../js/utils/constants';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
};

/**
 * @param {Object} propsData
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function createWrapper(propsData = {}, data = {}) {
    return shallowMount(ChartsMarkets, {
        localVue: mockVue(),
        propsData,
        data: () => data,
    });
}

const sanitizedMarketsOnTop = [
    {
        'pair': 'WEB/BTC',
        'change': '0%',
        'lastPrice': '0.01 BTC',
        'dayVolume': '2.21 BTC',
        'monthVolume': '2.21 BTC',
        'tokenUrl': '/coin/WEB/BTC',
        'lastPriceUSD': '386.74 USD',
        'dayVolumeUSD': '85469 USD',
        'monthVolumeUSD': '85469 USD',
        'marketCap': '0 BTC',
        'marketCapUSD': '0 USD',
        'buyDepth': '3.72 BTC',
        'buyDepthUSD': '143867 USD',
        'tokenized': false,
        'base': 'BTC',
        'quote': 'WEB',
        'baseImage': '',
        'quoteImage': '',
        'tokenizedImage': '',
        'rank': 0,
        'holders': 0,
        'createdOnMintmeSite': false,
        'showDeployedIcon': false,
    },
];

describe('ChartsMarkets.vue', () => {
    const url = 'https://www.mintme.com/';

    Object.defineProperty(window, 'location', {
        value: {
            href: url,
        },
        configurable: true,
    });

    it('should compute marketsOnTopIsLoaded correctly', () => {
        const wrapper = createWrapper({sanitizedMarketsOnTop});

        expect(wrapper.vm.marketsOnTopIsLoaded).toBe(1);
    });

    it('should return correctly url when the function redirectToMarket() is called', () => {
        const wrapper = createWrapper({sanitizedMarketsOnTop});

        const market = {
            base: 'BTC',
            quote: 'WEB',
        };

        wrapper.vm.redirectToMarket(market);
        expect(window.location.href).toEqual('coin');
    });

    it('should return negative labels, data and colors when floatChange is negative', () => {
        const wrapper = createWrapper({sanitizedMarketsOnTop});

        const floatChange = -5;

        expect(wrapper.vm.checkLabels(floatChange)).toEqual(CHART_NEGATIVE_DUMMY_DATA.labels);
        expect(wrapper.vm.checkData(floatChange)).toEqual(CHART_NEGATIVE_DUMMY_DATA.data);
        expect(wrapper.vm.checkBorderColor(floatChange)).toEqual(CHART_NEGATIVE_DUMMY_DATA.borderColor);
    });

    it('should return positive labels, data and colors when floatChange is positive', () => {
        const wrapper = createWrapper({sanitizedMarketsOnTop});

        const floatChange = 5;

        expect(wrapper.vm.checkLabels(floatChange)).toEqual(CHART_POSITIVE_DUMMY_DATA.labels);
        expect(wrapper.vm.checkData(floatChange)).toEqual(CHART_POSITIVE_DUMMY_DATA.data);
        expect(wrapper.vm.checkBorderColor(floatChange)).toEqual(CHART_POSITIVE_DUMMY_DATA.borderColor);
    });

    it('should return default labels, data and colors when floatChange equal 0', () => {
        const wrapper = createWrapper({sanitizedMarketsOnTop});

        const floatChange = 0;

        expect(wrapper.vm.checkLabels(floatChange)).toEqual(CHART_DEFAULT_DUMMY_DATA.labels);
        expect(wrapper.vm.checkData(floatChange)).toEqual(CHART_DEFAULT_DUMMY_DATA.data);
        expect(wrapper.vm.checkBorderColor(floatChange)).toEqual(CHART_DEFAULT_DUMMY_DATA.borderColor);
    });
});
