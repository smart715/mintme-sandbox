import {shallowMount, createLocalVue} from '@vue/test-utils';
import component from '../../js/components/token/introduction/TokenIntroductionStatistics';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
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

    it('lock-period request returns true', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(component, {localVue, propsData: {
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

        moxios.stubRequest('is_token_exchanged', {status: 200, response: true});

        moxios.wait(() => {
            expect(wrapper.vm.stats.releasePeriod).to.equal(10);
            expect(wrapper.vm.stats.hourlyRate).to.equal(1);
            expect(wrapper.vm.stats.releasedAmount).to.equal(1);
            expect(wrapper.vm.stats.frozenAmount).to.equal(1);
            done();
        });
    });

    it('lock-period request returns false', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(component, {localVue, propsData: {
            market: {
                base: {symbol: 'TOK1'}, quote: {symbol: 'TOK2'},
            },
        }});

        expect(wrapper.vm.stats.releasePeriod).to.equal('-');
        expect(wrapper.vm.stats.hourlyRate).to.equal('-');
        expect(wrapper.vm.stats.releasedAmount).to.equal('-');
        expect(wrapper.vm.stats.frozenAmount).to.equal('-');
    });
});
