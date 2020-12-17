import {shallowMount, createLocalVue} from '@vue/test-utils';
import Envelope from '../../js/components/Envelope';

const $routing = {generate: (val, params) => val};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = $routing;
        },
    });
    return localVue;
}

describe('Envelope', () => {
    it('dont show envelope icon', () => {
        const wrapper = shallowMount(Envelope, {
            localVue: mockVue(),
            propsData: {
                loggedIn: false,
                isOwner: false,
                dmMinAmount: '100',
                getQuoteBalance: '0',
                tokenName: 'Foo',
            },
        });

        expect(wrapper.find('a')).toBe(false);
    });
});
