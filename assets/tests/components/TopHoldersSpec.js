import {createLocalVue, mount} from '@vue/test-utils';
import TopHolders from '../../js/components/trade/TopHolders';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function mockTopHolders() {
    return mount(TopHolders, {
        localVue: mockVue(),
        propsData: {
            name: 'TOK1',
        },
    });
}

describe('TopHolders', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should show loading before getting the traders', (done) => {
        const wrapper = mockTopHolders();

        expect(wrapper.vm.loaded).to.be.false;
        expect(wrapper.find('font-awesome-icon').exists()).to.be.true;
        expect(wrapper.find('b-table').exists()).to.be.false;
        expect(wrapper.vm.traders).to.deep.equal(null);

        moxios.stubRequest('top_holders', {status: 200, response: [
            {
                user: {profile: {nickname: 'foo'}},
                timestamp: 1563550710,
                balance: '999',
            },
            {
                user: {profile: {nickname: 'foo'}},
                timestamp: 1563550710,
                balance: '99',
            },
            ]});

        moxios.wait(() => {
            expect(wrapper.vm.loaded).to.be.true;
            expect(wrapper.find('font-awesome-icon').exists()).to.be.false;
            expect(wrapper.find('b-table').exists()).to.be.true;
            done();
        });
    });

    it('should show arrow button if traders is null or more than 7', () => {
        const wrapper = mockTopHolders();
        const trader = {
                trader: 'foo baz',
                date: '19.07.2019 05:38:30',
                amount: 99,
            };

        expect(wrapper.vm.showDownArrow).to.be.false;

        wrapper.vm.traders = Array(7).fill(trader);
        expect(wrapper.vm.showDownArrow).to.be.false;

        wrapper.vm.traders = Array(8).fill(trader);
        expect(wrapper.vm.showDownArrow).to.be.true;
    });

    it('should hide the table if there are not traders', () => {
        const wrapper = mockTopHolders();
        wrapper.vm.traders = [];
        expect(wrapper.vm.hasTraders).to.be.false;
        expect(wrapper.find('b-table').exists()).to.be.false;
    });
});
