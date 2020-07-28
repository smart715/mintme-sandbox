import {mount} from '@vue/test-utils';
import TopHolders from '../../js/components/trade/HolderName';

describe('HolderName', () => {
    it('renders props correctly', () => {
        const wrapper = mount(TopHolders, {
            propsData: {
                value: 'foo',
                url: 'bar',
            },
        });

        expect(wrapper.find('a[href="bar"]').exists()).toBe(true);
        expect(wrapper.find('a').text()).toBe('foo');
    });
    it('updateTooltip works correctly', () => {
        const wrapper = mount(TopHolders, {
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
