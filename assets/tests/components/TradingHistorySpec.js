import '../../js/main';
import Vue from 'vue';
import TradingHistory from '../../js/components/wallet/TradingHistory';

describe('TradingHistory', () => {
    const vm = new Vue(TradingHistory).$mount();

    const websocketResult =
        [
            {
                'timestamp': 1551876719.890195,
                'side': 2,
                'amount': '5.000000000000000000',
                'price': '1.000000000000000000',
                'fee': '0.500000000000000000',
                'market': {
                    'token': {
                        'name': 'user110token',
                    },
                    'currencySymbol': 'WEB',
                    'hiddenName': 'TOK000000000010WEB',
                },
            },
            {
                'timestamp': 1551876719.890195,
                'side': 1,
                'amount': '5.000000000000000000',
                'price': '1.000000000000000000',
                'fee': '0.050000000000000000',
                'market': {
                    'token': {
                        'name': 'user110token',
                    },
                    'currencySymbol': 'WEB',
                    'hiddenName': 'TOK000000000010WEB',
                },
            },
            {
                'timestamp': 1551876704.610206,
                'side': 2,
                'amount': '5.000000000000000000',
                'price': '1.000000000000000000',
                'fee': '0.500000000000000000',
                'market': {
                    'token': {
                        'name': 'user110token',
                    },
                    'currencySymbol': 'WEB',
                    'hiddenName': 'TOK000000000010WEB',
                },
            },
            {
                'timestamp': 1551876704.610206,
                'side': 1,
                'amount': '5.000000000000000000',
                'price': '1.000000000000000000',
                'fee': '0.050000000000000000',
                'market': {
                    'token': {
                        'name': 'user110token',
                    },
                    'currencySymbol': 'WEB',
                    'hiddenName': 'TOK000000000010WEB'},
            },
        ];

    it('correctly sets history after axios request', () => {
        vm.history = websocketResult;
        expect(vm.history).to.deep.equal(websocketResult);
    });

    it('must calculate history length', () => {
        expect(vm.totalRows).to.deep.equal(websocketResult.length);
    });

    it('must determine history', () => {
        expect(vm.hasHistory).to.equal(true);
    });
});
