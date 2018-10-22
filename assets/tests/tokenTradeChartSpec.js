import '../js/main';
import TokenTradeChart from '../components/token/trade/TokenTradeChart';

describe('TokenTradeChart', () => {
    const Constructor = Vue.extend(TokenTradeChart);
    const vm = new Constructor({
        propsData: {
            marketName: '{' +
                '"hiddenName":"TOK000000000001WEB",' +
                '"tokenName":"tok1",' +
                '"currncySymbol":"WEB"' +
            '}',
        },
    }).$mount();

    it('market with volume 321 should be last in chart data', (done) => {
        const wsResult = {
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

        vm.wsResult = wsResult;

        Vue.nextTick(() => {
            expect(vm.chartData.datasets[0].data)
                .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 0, 321]);
            done();
        });
    });

    it('Market with volume 3210 should be after 321 in chart data', (done) => {
        const wsResult = {
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

        vm.wsResult = wsResult;

        Vue.nextTick(() => {
            expect(vm.chartData.datasets[0].data)
                .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 321, 3210]);
            done();
        });
    });
});
