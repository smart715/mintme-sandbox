import Vue from 'vue';
import TokenTradeChart from '../components/token/trade/TokenTradeChart';
import fontawesome from '@fortawesome/fontawesome';
import fas from '@fortawesome/fontawesome-free-solid';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

fontawesome.library.add(fas);
Vue.component('font-awesome-icon', FontAwesomeIcon);

describe('TokenTradeChart:', () => {
    it('Mock websocket data and check chart data', (done) => {
        const wsResult1 = {
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
        const wsResult2 = {
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

        vm.wsResult = wsResult1;

        Vue.nextTick(() => {
            expect(vm.chartData.datasets[0].data)
                .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 0, 321]);
            vm.wsResult = wsResult2;
            Vue.nextTick(() => {
              expect(vm.chartData.datasets[0].data)
                  .to.deep.equal([0, 0, 0, 0, 0, 0, 0, 0, 321, 3210]);
              done();
            });
            done();
        });
    });
});
