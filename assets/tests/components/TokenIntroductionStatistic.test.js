import {shallowMount, createLocalVue} from '@vue/test-utils';
import component from '../../js/components/token/introduction/TokenIntroductionStatistics';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
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
            modules: {
                tokenStatistics,
                websocket: {
                    namespaced: true,
                    actions: {
                        addMessageHandler: () => {},
                        addOnOpenHandler: () => {},
                    },
                },
            },
        });
        shallowMount(component, {store, localVue, propsData: {
            market: {
                base: {symbol: 'TOK1'}, quote: {symbol: 'TOK2'},
            },
        }});

        expect(tokenStatistics.state.stats.releasePeriod).toBe('-');
        expect(tokenStatistics.state.stats.hourlyRate).toBe('-');
        expect(tokenStatistics.state.stats.releasedAmount).toBe('-');
        expect(tokenStatistics.state.stats.frozenAmount).toBe('-');
    });

    it('lock-period request returns true', (done) => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                tokenStatistics,
                websocket: {
                    namespaced: true,
                    actions: {
                        addMessageHandler: () => {},
                        addOnOpenHandler: () => {},
                    },
                },
            },
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
        tokenStatistics.state.stats.releasePeriod = 10;
        tokenStatistics.state.stats.hourlyRate = 1;
        tokenStatistics.state.stats.releasedAmount = 1;
        tokenStatistics.state.stats.frozenAmount = 1;

        moxios.stubRequest('is_token_exchanged', {status: 200, response: true});

        moxios.wait(() => {
            expect(tokenStatistics.state.stats.releasePeriod).toBe(10);
            expect(tokenStatistics.state.stats.hourlyRate).toBe(1);
            expect(tokenStatistics.state.stats.releasedAmount).toBe(1);
            expect(tokenStatistics.state.stats.frozenAmount).toBe(1);
            done();
        });
    });
});
