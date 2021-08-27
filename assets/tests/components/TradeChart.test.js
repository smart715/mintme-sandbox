import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenTradeChart from '../../js/components/trade/TradeChart';
import axios from 'axios';
import Vuex from 'vuex';
jest.mock('axios');
axios.get = jest.fn(() => Promise.resolve());

// TODO: improve test and add more tests

describe('TradeChart', () => {
    describe('data field', () => {
        describe('marketStatus', () => {
            describe('when fetch market status from server', () => {
                const market = {
                    hiddenName: 'TOK000000000001WEB',
                    tokenName: 'tok1',
                    quote: {
                        symbol: 'testQuoteSymbol',
                        subunit: 4,
                    },
                    base: {
                        symbol: 'testBaseSymbol',
                        subunit: 4,
                    },
                };

                const localVue = createLocalVue();
                localVue.use(Vuex);
                localVue.use({
                    install(Vue, options) {
                        Vue.prototype.$axios = {retry: axios, single: axios};
                        Vue.prototype.$routing = {generate: (val) => val};
                        Vue.prototype.$toasted = {show: () => false};
                        Vue.prototype.$store = new Vuex.Store({
                            modules: {
                                websocket: {
                                    namespaced: true,
                                    actions: {
                                        addOnOpenHandler: jest.fn(),
                                    },
                                },
                            },
                        });
                        Vue.prototype.$t = (val) => val;
                    },
                });

                const wrapper = shallowMount(TokenTradeChart, {
                    localVue,
                    propsData: {
                        market,
                        websocketUrl: 'testWebsocketUrl',
                        mintmeSupplyUrl: 'testMintmeSupplyUrl',
                        minimumVolumeForMarketCap: 0,
                        buyDepth: '0',
                        isToken: true,
                    },
                });

                it('should update values correctly', (done) => {
                    wrapper.vm.messageHandler({
                        'method': 'state.update',
                        'params': [
                            'TOK000000000001WEB',
                            {
                                period: 86400,
                                last: '123',
                                open: '456',
                                close: '789',
                                high: '0',
                                low: '0',
                                volume: '321',
                                volumeDonation: '321',
                                deal: '0',
                                dealDonation: '0',
                            },
                        ],
                        'id': null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.marketStatus)
                                .toMatchObject({
                                    volume: '642',
                                    last: '123',
                                    change: '-73',
                                    amount: '0',
                                    monthVolume: '0',
                                    monthChange: '0',
                                    monthAmount: '0',
                                    marketCap: '0',
                                });
                            done();
                        });
                    });
                });

                it('should updates values correctly', (done) => {
                    wrapper.vm.messageHandler({
                        'method': 'state.update',
                        'params': [
                            'TOK000000000002WEB',
                            {
                                'period': 86400,
                                'last': '1230',
                                'open': '4560',
                                'close': '7890',
                                'high': '0',
                                'low': '0',
                                'volume': '3210',
                                'volumeDonation': '3210',
                                'deal': '0',
                                'dealDonation': '0',
                            },
                        ],
                        'id': null,
                    });

                    wrapper.vm.$nextTick(() => {
                        wrapper.vm.$nextTick(() => {
                            expect(wrapper.vm.marketStatus)
                                .toMatchObject({
                                    volume: '6420',
                                    last: '1230',
                                    change: '-73',
                                    amount: '0',
                                    monthVolume: '0',
                                    monthChange: '0',
                                    monthAmount: '0',
                                    marketCap: '0',
                                });
                            done();
                        });
                    });
                });
            });
        });
    });
});
