// import {createLocalVue, shallowMount} from '@vue/test-utils';
// import TradeOrders from '../../js/components/trade/TradeOrders';
// import {toMoney} from '../../js/utils';
// import moxios from 'moxios';
// import Axios from '../../js/axios';
//
// describe('TradeOrders', () => {
//     beforeEach(() => {
//         moxios.install();
//     });
//     afterEach(() => {
//         moxios.uninstall();
//     });
//     const $routing = {generate: () => 'URL'};
//
//     const localVue = createLocalVue();
//     localVue.use(Axios);
//
//     const wrapper = shallowMount(TradeOrders, {
//         localVue,
//         mocks: {
//             $routing,
//         },
//         propsData: {
//             ordersLoaded: false,
//             buyOrders: [],
//             sellOrders: [],
//             market: {
//                 base: {
//                     name: 'tok1',
//                     symbol: 'tok1',
//                     identifier: 'tok1',
//                 },
//                 quote: {
//                     name: 'Webchain',
//                     symbol: 'WEB',
//                     identifier: 'WEB',
//                 }
//             },
//             userId: 1,
//             precision: 8,
//         }
//     });
//
//     let order = {
//         price: toMoney(2),
//         amount: toMoney(2),
//         maker: {
//             id: 1,
//             profile: {
//                 firstName: 'foo',
//                 lastName: 'bar',
//             },
//         },
//         side: 1,
//         owner: false,
//     };
// });
