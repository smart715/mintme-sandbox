import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeSellOrder from '../../js/components/trade/TradeSellOrder';
import Axios from '../../js/axios';
import moxios from 'moxios';


describe('TradeSellOrder', () => {
    beforeEach(() => {
       moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });

    const $routing = {generate: () => 'URL'};

    const localVue = createLocalVue();
    localVue.use(Axios);

    const wrapper = shallowMount(TradeSellOrder, {
        localVue,
        mocks: {
            $routing,
        },
        propsData: {
            loginUrl: 'loginUrl',
            signupUrl: 'signupUrl',
            loggedIn: false,
            market: {
                base: {
                    name: 'Webchain',
                    symbol: 'WEB',
                    identifier: 'WEB',
                },
                quote: {
                    name: 'Betcoin',
                    symbol: 'BTC',
                    identifier: 'BTC',
                },
            },
            marketPrice: 2,
            isOwner: false,
        },
    });

    it('show login & logout buttons if not logged in', () => {
        expect(wrapper.find('a[href="loginUrl"]').exists()).to.deep.equal(true);
        expect(wrapper.find('a[href="signupUrl"]').exists()).to.deep.equal(true);
        wrapper.vm.loggedIn = true;
        expect(wrapper.find('a[href="loginUrl"]').exists()).to.deep.equal(false);
        expect(wrapper.find('a[href="signupUrl"]').exists()).to.deep.equal(false);
    });

    it('can make order if price and amount not null', (done) => {
        moxios.stubRequest(/.*/, {
            status: 200,
            response: {result: 1},
        });
        expect(wrapper.vm.showModal).to.deep.equal(false);
        wrapper.vm.placeOrder();
        expect(wrapper.vm.showModal).to.deep.equal(false);
        wrapper.vm.sellPrice = 2;
        wrapper.vm.sellAmount = 2;
        wrapper.vm.placeOrder();
        moxios.wait(() => {
            expect(wrapper.vm.showModal).to.deep.equal(true);
            done();
        });
    });

    it('trigger showModalAction correctly', function() {
        wrapper.vm.showModalAction();
        expect(wrapper.vm.modalSuccess).to.deep.equals(false);
        expect(wrapper.vm.modalTitle).to.deep.equals('Order Failed');

        wrapper.vm.showModalAction({result: 2});
        expect(wrapper.vm.modalSuccess).to.deep.equals(false);
        expect(wrapper.vm.modalTitle).to.deep.equals('Order Failed');

        wrapper.vm.showModalAction({result: 1});
        expect(wrapper.vm.modalSuccess).to.deep.equals(true);
        expect(wrapper.vm.modalTitle).to.deep.equals('Order Created');

        wrapper.vm.showModalAction({result: 2});
        expect(wrapper.vm.modalSuccess).to.deep.equals(false);
        expect(wrapper.vm.modalTitle).to.deep.equals('Order Failed');

        wrapper.vm.showModalAction({result: 1, message: 'Done'});
        expect(wrapper.vm.modalSuccess).to.deep.equals(true);
        expect(wrapper.vm.modalTitle).to.deep.equals('Done');
    });
});
