import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TraderHoveredMixin from '../../js/mixins/trader_hovered';

describe('TraderHoveredMixin', () => {
    const $url = 'URL';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [TraderHoveredMixin],
    });
    const basePrecision = 4;
    const wrapper = shallowMount(Component, {
        mocks: {
            $routing,
        },
    });

    let timestamp = Date.now();
    let orders = [
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 1, profile: {nickname: 'user1'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 1, profile: {nickname: 'user1'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 1, profile: {nickname: 'user1'}}},
        {price: '0.02', createdTimestamp: timestamp, maker: {id: 2, profile: {nickname: 'user2'}}},
        {price: '0.03', createdTimestamp: timestamp, maker: {id: 3, profile: {nickname: 'user3'}}},
        {price: '0.05', createdTimestamp: timestamp, maker: {id: 4, profile: {nickname: 'user4'}}},
        {price: '0.03', createdTimestamp: timestamp, maker: {id: 5, profile: {nickname: 'user5'}}},
        {price: '0.01', createdTimestamp: timestamp - 180, maker: {id: 6, profile: {nickname: 'user6'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 7, profile: {nickname: 'user7'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 8, profile: {nickname: 'user8'}}},
        {price: '0.01', createdTimestamp: timestamp - 150, maker: {id: 9, profile: {nickname: 'user9'}}},
        {price: '0.05', createdTimestamp: timestamp, maker: {id: 10, profile: {nickname: 'user10'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 11, profile: {nickname: 'user11'}}},
        {price: '0.03', createdTimestamp: timestamp, maker: {id: 12, profile: {nickname: 'user12'}}},
        {price: '0.01', createdTimestamp: timestamp, maker: {id: 13, profile: {nickname: 'user13'}}},
    ];

    it('should show tooltip content', () => {
        expect(wrapper.vm.tooltipContent).toBe('Loading...');
    });

    it('should return popover config object', () => {
        wrapper.vm.tooltipData = 'Loading...';
        expect(wrapper.vm.popoverConfig.title).toBe('Loading...');
        expect(wrapper.vm.popoverConfig.html).toBe(true);
        expect(wrapper.vm.popoverConfig.boundary).toBe('window');
        expect(wrapper.vm.popoverConfig.delay).toBe(0);
    });

    it('should create link to trader\'s profile from order data', () => {
        let order = {
            maker: {
                profile: {
                    nickname: 'foo',
                },
            },
        };
        let link = '<a href="' + $url + '">foo</a>';

        expect(wrapper.vm.createTraderLinkFromOrder(order)).toBe(link);
    });

    it('should contain link to trader\'s profile that is owner of three orders with same price', () => {
        let link = '<a href="' + $url + '">user1</a>';
        wrapper.vm.mouseoverHandler(
            orders.slice(0, 3),
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData).toBe(link);
    });

    it('should contain five links to trader\'s profiles `and 1 more.` for price 0.01', () => {
        let link1 = '<a href="' + $url + '">user1</a>';
        let link6 = '<a href="' + $url + '">user6</a>';
        let link7 = '<a href="' + $url + '">user7</a>';
        let link8 = '<a href="' + $url + '">user8</a>';
        let link9 = '<a href="' + $url + '">user9</a>';
        let link11 = '<a href="' + $url + '">user11</a>';

        wrapper.vm.mouseoverHandler(
            orders.slice(0, 13),
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData.includes(link1)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link6)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link7)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link8)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link9)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link11)).not.toBe(true);
        expect(wrapper.vm.tooltipData.includes('and 1 more.')).toBe(true);

        let tooltipData = [link6, link9, link1, link7, link8].join(', ');
        tooltipData += ' and 1 more.';
        expect(wrapper.vm.tooltipData).toBe(tooltipData);
    });

    it('should contain five links to trader\'s profiles `and 2 more.` for price 0.01', () => {
        let link1 = '<a href="' + $url + '">user1</a>';
        let link6 = '<a href="' + $url + '">user6</a>';
        let link7 = '<a href="' + $url + '">user7</a>';
        let link8 = '<a href="' + $url + '">user8</a>';
        let link9 = '<a href="' + $url + '">user9</a>';
        let link11 = '<a href="' + $url + '">user11</a>';
        let link13 = '<a href="' + $url + '">user13</a>';

        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData.includes(link1)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link6)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link8)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link9)).toBe(true);
        expect(wrapper.vm.tooltipData.includes(link11)).not.toBe(true);
        expect(wrapper.vm.tooltipData.includes(link13)).not.toBe(true);
        expect(wrapper.vm.tooltipData.includes('and 2 more.')).toBe(true);

        let tooltipData = [link6, link9, link1, link7, link8].join(', ');
        tooltipData += ' and 2 more.';
        expect(wrapper.vm.tooltipData).toBe(tooltipData);
    });

    it('should contain link to owner profile only for not existent price 0.476', () => {
        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.476'
        );

        expect(wrapper.vm.tooltipData).toBe('');
    });
});
