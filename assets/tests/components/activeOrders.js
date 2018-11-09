import '../../js/main';
import {mount} from '../testHelper';
import ActiveOrders from '../../components/wallet/ActiveOrders';

describe('ActiveOrders', () => {
    describe('get result from server', () => {
        const markets = {
            0: 'TOK000000000001WEB',
        };

        const vm = mount(ActiveOrders, {
            propsData: {
                markets: markets,
            },
        });
        const websocketResult = {
            'error': null,
            'result': {
                'limit': 100,
                'offset': 0,
                'total': 1,
                'records': [
                    {
                        'id': 481,
                        'market': 'TOK000000000001WEB',
                        'source': '',
                        'type': 1,
                        'side': 1,
                        'user': 1,
                        'ctime': 1541763111.4951439,
                        'mtime': 1541763111.4951439,
                        'price': '10',
                        'amount': '0.1',
                        'taker_fee': '0.1',
                        'maker_fee': '0.1',
                        'left': '0.1',
                        'deal_stock': '0',
                        'deal_money': '0',
                        'deal_fee': '0',
                        'unused_fee': '0',
                    },
                    {
                        'id': 480,
                        'market': 'TOK000000000001WEB',
                        'source': '',
                        'type': 1,
                        'side': 1,
                        'user': 1,
                        'ctime': 1541761035.4956639,
                        'mtime': 1541761035.4956639,
                        'price': '10',
                        'amount': '0.1',
                        'taker_fee': '0.1',
                        'maker_fee': '0.1',
                        'left': '0.1',
                        'deal_stock': '0',
                        'deal_money': '0',
                        'deal_fee': '0',
                        'unused_fee': '0',
                    },
                ],
            },
            'id': 1,
        };
        const websocketAfterRemove = {
            'error': null,
            'result': {
                'limit': 100,
                'offset': 0,
                'total': 1,
                'records': [
                    {
                        'id': 481,
                        'market': 'TOK000000000001WEB',
                        'source': '',
                        'type': 1,
                        'side': 1,
                        'user': 1,
                        'ctime': 1541763111.4951439,
                        'mtime': 1541763111.4951439,
                        'price': '10',
                        'amount': '0.1',
                        'taker_fee': '0.1',
                        'maker_fee': '0.1',
                        'left': '0.1',
                        'deal_stock': '0',
                        'deal_money': '0',
                        'deal_fee': '0',
                        'unused_fee': '0',
                    },
                ],
            },
            'id': 1,
        };
        describe(':remove', () => {
            context('after remove orders we must see count - 1', () => {
                vm.history = websocketAfterRemove;

                it('length should be equal after remove', (done) => {
                    Vue.nextTick(() => {
                        expect(vm.history).to.deep.equal(websocketAfterRemove);
                        done();
                    });
                });
            });
        });
    });
});
