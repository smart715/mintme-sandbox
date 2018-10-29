import '../../js/main';
import {mount} from '../testHelper';
import TokenTradeChart from '../../components/token/trade/TokenTradeChart';

describe('TokenTradeChart', () => {
    describe('data field', () => {
        describe(':chartData', () => {
            context('when component not mounted', () => {
                it('chart labels/data should be empty arrays', () => {
                    const data = TokenTradeChart.data();
                    expect(data.chartData.labels).to.deep.equal([]);
                    expect(data.chartData.datasets[0].data).to.deep.equal([]);
                });
            });

            context('when component mounted', () => {
                it('chart labels/data should be arrays of zeros', () => {
                    const vm = mount(TokenTradeChart, {});

                    const labelsLength = vm.chartData.labels.length;
                    const labelsSum = vm.chartData.labels.reduce((total, num) => {
                        return total + num;
                    });
                    const dataLength = vm.chartData.datasets[0].data.length;
                    const dataSum = vm.chartData.datasets[0].data.reduce((total, num) => {
                        return total + num;
                    });

                    expect(labelsLength).to.equal(vm.chartXAxesPoints);
                    expect(labelsSum).to.equal(0);
                    expect(dataLength).to.equal(vm.chartXAxesPoints);
                    expect(dataSum).to.equal(0);
                });
            });

            context('when fetch market status from server', () => {
                const market = JSON.stringify({
                    hiddenName: 'TOK000000000001WEB',
                    tokenName: 'tok1',
                    currncySymbol: 'WEB',
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
                        expect(vm.chartData.datasets[0].data)
                            .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 0, 321]);
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
                        expect(vm.chartData.datasets[0].data)
                            .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 321, 3210]);
                        done();
                    });
                });
            });
        });
    });
});
