import {shallowMount, createLocalVue} from '@vue/test-utils';
import BuyCryptoModal from '../../js/components/modal/BuyCryptoModal.vue';

const coinifyUiUrlTest = 'https://trade-ui.sandbox.coinify.com';

const coinifyCryptoCurrenciesTest = [
    'BTC',
    'ETH',
    'USDC',
    'BNB',
];
const cryptoListForTranslationTest = 'BNB, BTC, CRO, ETH and USDC';

const predefinedTokensTest = [
    {
        'identifier': 'BNB',
        'fullname': 'Binance Coin',
        'subunit': 8,
        'cryptoSymbol': 'BNB',
        'name': 'BNB',
        'url': '/coin/MINTME/BNB',
    },
    {
        'identifier': 'BTC',
        'fullname': 'Bitcoin',
        'subunit': 8,
        'cryptoSymbol': 'BTC',
        'name': 'BTC',
        'url': '/coin/MINTME/BTC',
    },
    {
        'identifier': 'CRO',
        'fullname': 'Cronos',
        'subunit': 8,
        'cryptoSymbol': 'CRO',
        'name': 'CRO',
        'url': '/coin/MINTME/CRO',
    },
    {
        'identifier': 'ETH',
        'fullname': 'Ethereum',
        'subunit': 8,
        'cryptoSymbol': 'ETH',
        'name': 'ETH',
        'url': '/coin/MINTME/ETH',
    },
    {
        'identifier': 'USDC',
        'fullname': 'USD Coin',
        'subunit': 6,
        'cryptoSymbol': 'USDC',
        'name': 'USDC',
        'url': '/coin/MINTME/USDC',
    },
    {
        'identifier': 'WEB',
        'fullname': 'Webchain',
        'subunit': 4,
        'cryptoSymbol': 'WEB',
        'name': 'WEB',
        'url': '/coin/MINTME/BTC',
    },
];

const paramsTest = {
    partnerId: 135,
    cryptoCurrencies: 'BTC,ETH,USDC,BNB',
    primaryColor: '0E3B58',
    fontColor: 'gray',
    address: '',
    addressSignature: '',
    refreshToken: 'undefined',
    addressConfirmation: true,
};

const frameSrcQueryStrTest = Object.keys(paramsTest).map(
    (key) => key + '=' + paramsTest[key]
).join('&');


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
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockBuyCryptoModal(props = {}) {
    return shallowMount(BuyCryptoModal, {
        localVue: mockVue(),
        propsData: {
            visible: false,
            uiUrl: coinifyUiUrlTest,
            partnerId: 135,
            addresses: {},
            addressesSignature: {},
            cryptoCurrencies: coinifyCryptoCurrenciesTest,
            predefinedTokens: predefinedTokensTest,
            ...props,
        },
    });
}

describe('BuyCryptoModal', () => {
    it('shouldn\'t be visible when visible props is false', () => {
        const wrapper = mockBuyCryptoModal();

        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe(undefined);
    });

    it('Should be visible when visible props is true', async () => {
        const wrapper = mockBuyCryptoModal();

        await wrapper.setProps({visible: true});
        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe('true');
    });

    it('Verify that "generateFullUrl" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();
        delete window.location;
        window.location = {
            host: 'https://mintme.com',
        };

        expect(wrapper.vm.generateFullUrl('/coin')).toBe('https://mintme.com/coin');
    });

    it('Verify that "cryptoToExchangeWithMintme" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        const cryptos = predefinedTokensTest.slice(0, predefinedTokensTest.length - 1);

        expect(wrapper.vm.cryptoToExchangeWithMintme).toEqual(cryptos);
    });

    it('Verify that "cryptoListForTranslation" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        expect(wrapper.vm.cryptoListForTranslation).toBe(cryptoListForTranslationTest);
    });

    it('Verify that "translationContext" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        const cryptoListTest = {
            cryptosList: cryptoListForTranslationTest,
        };

        expect(wrapper.vm.translationContext).toStrictEqual(cryptoListTest);
    });

    it('Verify that "frameSrcParams" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        expect(wrapper.vm.frameSrcParams).toStrictEqual(paramsTest);
    });

    it('Verify that "frameSrcQueryStr" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        expect(wrapper.vm.frameSrcQueryStr).toBe(frameSrcQueryStrTest);
    });

    it('Verify that "frameSrc" returns the correct value', () => {
        const wrapper = mockBuyCryptoModal();

        expect(wrapper.vm.frameSrc).toBe(`${coinifyUiUrlTest}/widget?${frameSrcQueryStrTest}`);
    });
});
