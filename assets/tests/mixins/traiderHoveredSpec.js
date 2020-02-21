import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TraiderHoveredMixin from '../../js/mixins/trader_hovered';

describe('TraiderHoveredMixin', function() {
    const $url = 'URL';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        mixins: [TraiderHoveredMixin],
    });

    const wrapper = shallowMount(Component, {
        mocks: {
            $routing,
        },
    });

    it('should show tooltip content', () => {
        expect(wrapper.vm.tooltipContent).to.be.equal('Loading...');
    });

    it('should return popover config object', () => {
        wrapper.vm.tooltipData = 'Loading...';
        expect(wrapper.vm.popoverConfig.title).to.be.equal('Loading...');
        expect(wrapper.vm.popoverConfig.html).to.be.true;
        expect(wrapper.vm.popoverConfig.boundary).to.be.equal('viewport');
    });

    it('should not react (tooltip content not changing) on hover event', () => {
        let order = {
            maker: {
                profile: {
                    firstName: 'User',
                    lastName: 'Test',
                    page_url: 'test-user',
                },
            },
        };
        let link = '<a href="' + $url + '">User Test</a>';

        expect(wrapper.vm.createTraderLinkFromOrder(order)).to.be.equal(link);
    });
});
