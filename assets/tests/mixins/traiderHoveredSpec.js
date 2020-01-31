import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TraiderHoveredMixin from '../../js/mixins/trader_hovered';

describe('TraiderHoveredMixin', function() {
    const Component = Vue.component('foo', {
        mixins: [TraiderHoveredMixin],
    });

    const wrapper = shallowMount(Component, {
        propsData: {
            loggedIn: false,
        },
    });

    it('should show tooltip content', () => {
        expect(wrapper.vm.tooltipContent).to.be.equal('Loading...');

        wrapper.vm.tooltipData = 'No data.';
        expect(wrapper.vm.tooltipContent).to.be.equal('No data.');
    });

    it('should return popover config object', () => {
        wrapper.vm.tooltipData = 'Loading...';
        expect(wrapper.vm.popoverConfig.title).to.be.equal('Loading...');
        expect(wrapper.vm.popoverConfig.html).to.be.true;
        expect(wrapper.vm.popoverConfig.boundary).to.be.equal('viewport');
        expect(wrapper.vm.popoverConfig.show).to.be.equal(300);
        expect(wrapper.vm.popoverConfig.hide).to.be.equal(100);
    });

    it('should return order side', () => {
        wrapper.vm.side = '1';
        expect(wrapper.vm.orderSide).to.be.equal('1');

        wrapper.vm.side = '2';
        expect(wrapper.vm.orderSide).to.be.equal('2');
    });

    it('should not react (tooltip content not changing) on hover event', () => {
        wrapper.vm.mouseoverHandler();
        expect(wrapper.vm.tooltipContent).to.be.equal('Loading...');

        wrapper.vm.loggedIn = true;
        wrapper.vm.isLoading = true;
        wrapper.vm.mouseoverHandler();
        expect(wrapper.vm.tooltipContent).to.be.equal('Loading...');
    });
});
