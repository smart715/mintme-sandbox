import {shallowMount, createLocalVue} from '@vue/test-utils';
import TopHolders from '../../js/components/trade/HolderName';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('b-tooltip', {});
    return localVue;
}

describe('HolderName', () => {
    it('renders props correctly', () => {
        const wrapper = shallowMount(TopHolders, {
            localVue: mockVue(),
            propsData: {
                value: 'foo',
                url: 'bar',
            },
        });

        expect(wrapper.find('a[href="bar"]').exists()).toBe(true);
        expect(wrapper.find('a').text()).toBe('foo');
    });
    it('updateTooltip works correctly', () => {
        const wrapper = shallowMount(TopHolders, {
            localVue: mockVue(),
            propsData: {
                value: 'foo',
                url: 'bar',
            },
        });
        expect(wrapper.vm.disableTooltip).toBe(false);

        wrapper.vm.updateTooltip(false);

        expect(wrapper.vm.disableTooltip).toBe(true);
    });
});
