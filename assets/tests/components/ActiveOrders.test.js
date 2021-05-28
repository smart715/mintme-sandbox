import {createLocalVue, shallowMount} from '@vue/test-utils';
import ActiveOrders from '../../js/components/wallet/ActiveOrders';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('ActiveOrders', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('get tableData from server correctly', (done) => {
        const tableData = {
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

        moxios.stubRequest('orders', {
            status: 200,
            response: {
                data: tableData,
            },
        });

        const wrapper = shallowMount(ActiveOrders, {
            localVue: mockVue(),
            propsData: {
                markets: ['TOK000000000001WEB'],
                websocketUrl: '',
            },
        });

        moxios.wait(() => {
            expect(wrapper.vm.tableData).toEqual([tableData]);
            done();
        });
    });
});

// todo add extra tests
