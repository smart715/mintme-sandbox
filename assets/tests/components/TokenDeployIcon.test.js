import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenDeployIcon from '../../js/components/token/deploy/TokenDeployIcon';

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
    return localVue;
}

describe('TokenDeployIcon', () => {
    it('do not show if owner & not deployed', () => {
        const wrapper = shallowMount(TokenDeployIcon, {
            localVue: mockVue(),
            propsData: {
                isOwner: true,
                statusProp: 'not-deployed',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).toBe(false);
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
        expect(wrapper.find('.not-deployed-icon').exists()).toBe(true);
    });

    it('do not show if not owner & not deployed', () => {
        const wrapper = shallowMount(TokenDeployIcon, {
            localVue: mockVue(),
            propsData: {
                isOwner: false,
                statusProp: 'not-deployed',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).toBe(false);
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
        expect(wrapper.find('.not-deployed-icon').exists()).toBe(true);
    });

    it('show pending icon if owner & pending', () => {
        const wrapper = shallowMount(TokenDeployIcon, {
            localVue: mockVue(),
            propsData: {
                isOwner: true,
                statusProp: 'pending',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).toBe(false);
        expect(wrapper.findAll('.loading-spinner').exists()).toBe(true);
        expect(wrapper.find('.not-deployed-icon').exists()).toBe(false);
    });

    it('do not show pending icon if not owner & pending', () => {
        const wrapper = shallowMount(TokenDeployIcon, {
            localVue: mockVue(),
            propsData: {
                isOwner: false,
                statusProp: 'pending',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).toBe(false);
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
        expect(wrapper.find('.not-deployed-icon').exists()).toBe(false);
    });

    it('show deployed icon if deployed', () => {
        const wrapper = shallowMount(TokenDeployIcon, {
            localVue: mockVue(),
            propsData: {
                statusProp: 'deployed',
                tokenCrypto: {
                    symbol: 'WEB',
                },
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).toBe(true);
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
        expect(wrapper.find('.not-deployed-icon').exists()).toBe(false);
    });
});
