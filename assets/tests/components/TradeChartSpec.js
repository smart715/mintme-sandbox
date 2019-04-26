import '../../js/main';
import {mount} from '../testHelper';
import TokenTradeChart from '../../js/components/trade/TradeChart';

describe('TradeChart', () => {
    describe('data field', () => {
        describe(':chartData', () => {
            context('when fetch market status from server', () => {
                const market = JSON.stringify({
                    hiddenName: 'TOK000000000001WEB',
                    tokenName: 'tok1',
                    currencySymbol: 'WEB',
                });
                const vm = mount(TokenTradeChart, {
                    propsData: {
                        marketName: market,
                    },
                });

                it('volume 321 should add to the end of chart data', (done) => {
                    vm.wsResult = {
                        'method': 'state.update',
                        'params': [
                            'TOK000000000001WEB',
                            {
                                'period': 86400,
                                'last': '123',
                                'open': '456',
                                'close': '789',
                                'high': '0',
                                'low': '0',
                                'volume': '321',
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.chartData.datasets[0].data)
                                .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 0, 321]);
                            done();
                        });
                        done();
                    });
                });

                it('volume 3210 should add to the end of chart data after 321', (done) => {
                    vm.wsResult = {
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
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.chartData.datasets[0].data)
                                .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 321, 3210]);
                            done();
                        });
                        done();
                    });
                });
            });
        });
    });
});
