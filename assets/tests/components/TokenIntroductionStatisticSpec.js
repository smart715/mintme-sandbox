import {shallowMount, createLocalVue} from '@vue/test-utils';
import component from '../../js/components/token/introduction/TokenIntroductionStatistics';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStats from '../../js/storage/modules/token_statistics';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('TokenIntroductionStatistics', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });
    it('lock-period request returns false', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        shallowMount(component, {store, localVue, propsData: {
            market: {
                base: {symbol: 'TOK1'}, quote: {symbol: 'TOK2'},
            },
        }});

        expect(tokenStats.state.stats.releasePeriod).to.equal('-');
        expect(tokenStats.state.stats.hourlyRate).to.equal('-');
        expect(tokenStats.state.stats.releasedAmount).to.equal('-');
        expect(tokenStats.state.stats.frozenAmount).to.equal('-');
    });
     it('lock-period request returns true', (done) => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        shallowMount(component, {store, localVue, propsData: {
            market: {
                base: {symbol: 'TOK1'}, quote: {symbol: 'TOK2'},
            },
        }});

        moxios.stubRequest('lock-period', {status: 200, response: {
           releasePeriod: 10,
           hourlyRate: 1,
           releasedAmount: 1,
           frozenAmount: 1,
        }});
        tokenStats.state.stats.releasePeriod = 10;
        tokenStats.state.stats.hourlyRate = 1;
        tokenStats.state.stats.releasedAmount = 1;
        tokenStats.state.stats.frozenAmount = 1;

        moxios.stubRequest('is_token_exchanged', {status: 200, response: true});

        moxios.wait(() => {
            expect(tokenStats.state.stats.releasePeriod).to.be.equal(10);
            expect(tokenStats.state.stats.hourlyRate).to.equal(1);
            expect(tokenStats.state.stats.releasedAmount).to.equal(1);
            expect(tokenStats.state.stats.frozenAmount).to.equal(1);
            done();
        });
    });
});
