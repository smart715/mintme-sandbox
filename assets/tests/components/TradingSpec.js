import '../../js/main';
import {mount} from '../testHelper';
import Trading from '../../js/components/trading/Trading';
import bPagination from 'bootstrap-vue/es/components/pagination/pagination';
import bTable from 'bootstrap-vue/es/components/table/table';

Vue.component('b-pagination', bPagination);
Vue.component('b-table', bTable);

describe('Trading', () => {
    describe('data field', () => {
        describe(':tokens', () => {
            context('when fetch markets from server', () => {
                const markets = JSON.stringify({
                    TOK000000000001WEB: ['tok1', 'WEB'],
                    TOK000000000002WEB: ['tok2', 'WEB'],
                    TOK000000000003BTC: ['WEB', 'BTC'],
                });
                const vm = mount(Trading, {
                    propsData: {
                        marketNames: markets,
                    },
                });

                it('should contain WEB/tok1', (done) => {
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
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });

                it('should contain WEB/BTC before WEB/tok1', (done) => {
                    vm.wsResult = {
                        'method': 'state.update',
                        'params': [
                            'TOK000000000003BTC',
                            {
                                'period': 86400,
                                'last': '12',
                                'open': '45',
                                'close': '78',
                                'high': '0',
                                'low': '0',
                                'volume': '32',
                                'deal': '0',
                            },
                        ],
                        'id': null,
                    };

                    Vue.nextTick(() => {
                        Vue.nextTick(() => {
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/BTC', change: '-73.33', lastPrice: '12.00', volume: '32.00'},
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });

                it('should contain WEB/tok2 before WEB/tok1', (done) => {
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
                            expect(vm.tokens).to.deep.equal([
                                {pair: 'WEB/BTC', change: '-73.33', lastPrice: '12.00', volume: '32.00'},
                                {pair: 'WEB/tok2', change: '-73.03', lastPrice: '1230.00', volume: '3210.00'},
                                {pair: 'WEB/tok1', change: '-73.03', lastPrice: '123.00', volume: '321.00'},
                            ]);
                            done();
                        });
                        done();
                    });
                });
            });
        });
    });
});
