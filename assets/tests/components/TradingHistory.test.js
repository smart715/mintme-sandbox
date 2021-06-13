import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradingHistory from '../../js/components/wallet/TradingHistory';
import axios from 'axios';

describe('TradingHistory', () => {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$sortCompare = () => {};
            Vue.prototype.$t = (val) => val;
        },
    });

    const el = shallowMount(TradingHistory, {
        localVue,
    });

    const tableData = [
        {
            'timestamp': 1551876719.890195,
            'side': 2,
            'amount': '5.000000000000000000',
            'price': '1.000000000000000000',
            'fee': '0.500000000000000000',
            'market': {
                'base': {
                    'subunit': 4,
                },
                'quote': {
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
                'base': {
                    'subunit': 4,
                },
                'quote': {
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
                'base': {
                    'subunit': 4,
                },
                'quote': {
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
                'base': {
                    'subunit': 4,
                },
                'quote': {
                    'name': 'user110token',
                },
                'currencySymbol': 'WEB',
                'hiddenName': 'TOK000000000010WEB'},
        },
    ];

    it('must determine history', () => {
        el.setData({tableData});
        expect(el.vm.hasHistory).toBe(true);
    });
});
