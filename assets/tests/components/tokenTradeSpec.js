import '../../js/main';
import {mount} from '../testHelper';
import TokenTrade from '../../components/token/trade/TokenTrade';

describe('TokenTrade', () => {
    describe('data field', () => {
        const market = JSON.stringify({
            hiddenName: 'TOK000000000001WEB',
            tokenName: 'tok1',
            currncySymbol: 'WEB',
        });
        const vm = mount(TokenTrade, {
            propsData: {
                marketName: market,
            },
        });
        const websocketResult = {
            'method': 'deals.update',
            'params': [
                'TOK000000000001WEB',
                [
                    {
                        'id': 7,
                        'time': 1538991286.2673919,
                        'price': '0.75',
                        'amount': '11',
                        'type': 'sell',
                    },
                    {
                        'id': 6,
                        'time': 1538998561.7105019,
                        'price': '7',
                        'amount': '11',
                        'maker_id': 1,
                        'taker_id': 1,
                        'type': 'buy',
                    },
                    {
                        'id': 5,
                        'time': 1538995609.6422961,
                        'price': '3',
                        'amount': '1',
                        'maker_id': 1,
                        'taker_id': 1,
                        'type': 'sell',
                    },
                    {
                        'id': 4,
                        'time': 1538991376.9309659,
                        'price': '1',
                        'amount': '59',
                        'type': 'buy',
                    },
                    {
                        'id': 3,
                        'time': 1538991320.084486,
                        'price': '0.5',
                        'amount': '26',
                        'type': 'sell',
                    },
                    {
                        'id': 2,
                        'time': 1538991311.995465,
                        'price': '2',
                        'amount': '18',
                        'type': 'buy',
                    },
                    {
                        'id': 1,
                        'time': 1536654786.424314,
                        'price': '0.1',
                        'amount': '500',
                        'type': 'buy',
                    },
                ],
            ],
            'id': null,
        };

        describe(':buy', () => {
            context('when fetch markets deal from server', () => {
                vm.wsResult = websocketResult;

                it('should set with minimum price of pending markets', (done) => {
                    Vue.nextTick(() => {
                        expect(vm.buy).to.deep.equal({
                            amount: 26,
                            price: .5,
                        });
                        done();
                    });
                });
            });
        });

        describe(':sell', () => {
            context('when fetch markets deal from server', () => {
                vm.wsResult = websocketResult;

                it('should set with maximum price of pending markets', (done) => {
                    Vue.nextTick(() => {
                        expect(vm.sell).to.deep.equal({
                            amount: 18,
                            price: 2,
                        });
                        done();
                    });
                });
            });
        });
    });
});
