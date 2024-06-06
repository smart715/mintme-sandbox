import {shallowMount, createLocalVue} from '@vue/test-utils';
import KeyFacts from '../../js/components/coin/KeyFacts.vue';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
*/
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    localVue.use(Vuex);

    return localVue;
}

describe('KeyFacts', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(KeyFacts, {
            localVue: mockVue(),
        });
    });

    it('renders the correct number of key facts', () => {
        const count = wrapper.vm.keyFactsTrans.header.length;
        expect(wrapper.findAll('.key-fact').length).toBe(count);
    });

    it('renders the correct key fact headers', () => {
        const expectedHeaders = wrapper.vm.keyFactsTrans.header;

        const headers = wrapper.findAll('.key-fact-header');

        headers.wrappers.forEach((header, index) => {
            expect(header.text()).toBe(expectedHeaders[index]);
        });
    });

    it('renders the correct key fact texts', () => {
        const expectedTexts = wrapper.vm.keyFactsTrans.text;

        const texts = wrapper.findAll('.key-fact-text');

        texts.wrappers.forEach((text, index) => {
            expect(text.text()).toBe(expectedTexts[index]);
        });
    });

    it('renders the correct key fact animation classes', () => {
        const expectedAnimationClasses = [
            'slide-right',
            'zoom-in',
            'slide-left',
            'slide-right',
            'zoom-in',
            'slide-left',
            'slide-right',
            'zoom-in',
            'slide-left',
        ];
        const keyFacts = wrapper.findAll('.key-fact');

        keyFacts.wrappers.forEach((keyFact, index) => {
            expect(keyFact.attributes('data-aos')).toBe(expectedAnimationClasses[index]);
        });
    });
});
