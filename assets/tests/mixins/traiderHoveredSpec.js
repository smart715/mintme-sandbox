import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TraiderHoveredMixin from '../../js/mixins/trader_hovered';

describe('TraiderHoveredMixin', function() {
    const $url = 'URL';
    const $anon = 'Anonymous';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        mixins: [TraiderHoveredMixin],
    });
    const basePrecision = 4;
    const wrapper = shallowMount(Component, {
        mocks: {
            $routing,
        },
    });

    let timestamp = Date.now();
    let orders = [
        {price: '0.01', timestamp: timestamp, maker: {id: 1, profile: {firstName: 'User1', lastName: 'Test1', page_url: 'test-user'}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 1, profile: {firstName: 'User1', lastName: 'Test1', page_url: 'test-user'}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 1, profile: {firstName: 'User1', lastName: 'Test1', page_url: 'test-user'}}},
        {price: '0.02', timestamp: timestamp, maker: {id: 2, profile: {firstName: 'User2', lastName: 'Test2', page_url: 'test-user', anonymous: true}}},
        {price: '0.03', timestamp: timestamp, maker: {id: 3, profile: {firstName: 'User3', lastName: 'Test3', page_url: 'test-user', anonymous: true}}},
        {price: '0.05', timestamp: timestamp, maker: {id: 4, profile: {firstName: 'User4', lastName: 'Test4', page_url: 'test-user', anonymous: false}}},
        {price: '0.03', timestamp: timestamp, maker: {id: 5, profile: null}},
        {price: '0.01', timestamp: timestamp, maker: {id: 6, profile: {firstName: 'User6', lastName: 'Test6', page_url: 'test-user'}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 7, profile: {firstName: 'User7', lastName: 'Test7', page_url: 'test-user', anonymous: true}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 8, profile: {firstName: 'User8', lastName: 'Test8', page_url: 'test-user'}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 9, profile: {firstName: 'User9', lastName: 'Test9', page_url: 'test-user', anonymous: false}}},
        {price: '0.05', timestamp: timestamp, maker: {id: 10, profile: {firstName: 'User10', lastName: 'Test10', page_url: 'test-user', anonymous: true}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 11, profile: {firstName: 'User11', lastName: 'Test11', page_url: 'test-user', anonymous: true}}},
        {price: '0.03', timestamp: timestamp, maker: {id: 12, profile: {firstName: 'User12', lastName: 'Test12', page_url: 'test-user', anonymous: true}}},
        {price: '0.01', timestamp: timestamp, maker: {id: 13, profile: {firstName: 'User13', lastName: 'Test13', page_url: 'test-user', anonymous: true}}},
    ];

    it('should show tooltip content', () => {
        expect(wrapper.vm.tooltipContent).to.be.equal('Loading...');
    });

    it('should return popover config object', () => {
        wrapper.vm.tooltipData = 'Loading...';
        expect(wrapper.vm.popoverConfig.title).to.be.equal('Loading...');
        expect(wrapper.vm.popoverConfig.html).to.be.true;
        expect(wrapper.vm.popoverConfig.boundary).to.be.equal('viewport');
        expect(wrapper.vm.popoverConfig.delay).to.be.equal(0);
    });

    it('should create link to trader\'s profile from user profile', () => {
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

        expect(wrapper.vm.createTraderLinkFromProfile(order.maker.profile)).to.be.equal(link);
    });

    it('should return Anonymous for anonymous user profile or if no user profile', () => {
        let order = {
            maker: {
                profile: {
                    anonymous: true,
                },
            },
        };

        expect(wrapper.vm.createTraderLinkFromProfile(order.maker.profile)).to.be.equal($anon);
        expect(wrapper.vm.createTraderLinkFromProfile(null)).to.be.equal($anon);
    });

    it('should contain link to trader\'s profile that is owner of three orders with same price', () => {
        let link = '<a href="' + $url + '">User1 Test1</a>';
        wrapper.vm.mouseoverHandler(
            orders.slice(0, 3),
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData).to.contain(link);
    });

    it('should contain two links to trader\'s profiles and one Anonymous for price 0.01', () => {
        let link1 = '<a href="' + $url + '">User1 Test1</a>';
        let link6 = '<a href="' + $url + '">User6 Test6</a>';

        wrapper.vm.mouseoverHandler(
            orders.slice(0, 9),
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData).to.contain(link1);
        expect(wrapper.vm.tooltipData).to.contain(link6);
        expect(wrapper.vm.tooltipData).to.contain($anon);
    });

    it('should contain five links to trader\'s profiles `and 1 more.` for price 0.01', () => {
        let link1 = '<a href="' + $url + '">User1 Test1</a>';
        let link6 = '<a href="' + $url + '">User6 Test6</a>';
        let link8 = '<a href="' + $url + '">User8 Test8</a>';
        let link9 = '<a href="' + $url + '">User9 Test9</a>';
        let link11 = '<a href="' + $url + '">User11 Test11</a>';

        wrapper.vm.mouseoverHandler(
            orders.slice(0, 13),
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData).to.contain(link1);
        expect(wrapper.vm.tooltipData).to.contain(link6);
        expect(wrapper.vm.tooltipData).to.contain($anon);
        expect(wrapper.vm.tooltipData).to.contain(link8);
        expect(wrapper.vm.tooltipData).to.contain(link9);
        expect(wrapper.vm.tooltipData).to.not.contain(link11);
        expect(wrapper.vm.tooltipData).to.contain('and 1 more.');
    });

    it('should contain five links to trader\'s profiles `and 2 more.` for price 0.01', () => {
        let link1 = '<a href="' + $url + '">User1 Test1</a>';
        let link6 = '<a href="' + $url + '">User6 Test6</a>';
        let link8 = '<a href="' + $url + '">User8 Test8</a>';
        let link9 = '<a href="' + $url + '">User9 Test9</a>';
        let link11 = '<a href="' + $url + '">User11 Test11</a>';
        let link13 = '<a href="' + $url + '">User13 Test13</a>';

        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.01'
        );

        expect(wrapper.vm.tooltipData).to.contain(link1);
        expect(wrapper.vm.tooltipData).to.contain(link6);
        expect(wrapper.vm.tooltipData).to.contain($anon);
        expect(wrapper.vm.tooltipData).to.contain(link8);
        expect(wrapper.vm.tooltipData).to.contain(link9);
        expect(wrapper.vm.tooltipData).to.not.contain(link11);
        expect(wrapper.vm.tooltipData).to.not.contain(link13);
        expect(wrapper.vm.tooltipData).to.contain('and 2 more.');
    });

    it('should contain three Anonymous for anonymous owner and price 0.03', () => {
        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.03'
        );

        expect(wrapper.vm.tooltipData).to.be.equal('Anonymous, Anonymous, Anonymous');
    });

    it('should contain link to owner profile only for not existent price 0.476', () => {
        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.476'
        );

        expect(wrapper.vm.tooltipData).to.be.equal('');
    });

    it('should show other traders for buy executed and pending orders for price 0.05', () => {
        wrapper.vm.executedOrders = [
            {price: '0.07', side: 2, timestamp: timestamp, maker: {id: 14, profile: null}},
            {price: '0.06', side: 2, timestamp: timestamp, maker: {id: 15, profile: null}},
            {
                price: '0.05',
                timestamp: timestamp - 120,
                side: 2,
                maker: {
                    id: 16,
                    profile: {
                        firstName: 'Maker16',
                        lastName: 'Maker16',
                        page_url: 'test-maker16',
                    },
                },
                taker: {
                    id: 17,
                    profile: {
                        firstName: 'Taker17',
                        lastName: 'Taker17',
                        page_url: 'test-taker17',
                    },
                },
            },
        ];

        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '0.05',
            2
        );

        let link16 = '<a href="' + $url + '">Maker16 Maker16</a>';
        let link17 = '<a href="' + $url + '">Taker17 Taker17</a>';
        let link4 = '<a href="' + $url + '">User4 Test4</a>';

        expect(wrapper.vm.tooltipData).to.contain(link16);
        expect(wrapper.vm.tooltipData).to.contain(link17);
        expect(wrapper.vm.tooltipData).to.contain(link4);
        expect(wrapper.vm.tooltipData).to.contain($anon);
        expect(wrapper.vm.tooltipData).to.be.equal([link16, link17, link4, $anon].join(', '));
    });

    it('should show other traders for sell executed and pending orders for price 3', () => {
        wrapper.vm.executedOrders = [
            {price: '3', side: 1, timestamp: timestamp - 180, maker: {id: 18, profile: {firstName: 'Maker18', lastName: 'Maker18', page_url: 'test-user'}}},
            {price: '7', side: 1, timestamp: timestamp, maker: {id: 19, profile: {firstName: 'User19', lastName: 'Test19', page_url: 'test-user'}}},
            {price: '4', side: 2, timestamp: timestamp, maker: {id: 20, profile: {firstName: 'User20', lastName: 'Test20', page_url: 'test-user'}}},
            {price: '3', side: 1, timestamp: timestamp - 300, maker: {id: 21, profile: {firstName: 'Maker21', lastName: 'Maker21', page_url: 'test-user'}}},
        ];

        wrapper.vm.mouseoverHandler(
            orders,
            basePrecision,
            '3',
            1
        );

        let link18 = '<a href="' + $url + '">Maker18 Maker18</a>';
        let link21 = '<a href="' + $url + '">Maker21 Maker21</a>';

        expect(wrapper.vm.tooltipData).to.contain(link18);
        expect(wrapper.vm.tooltipData).to.contain(link21);
        expect(wrapper.vm.tooltipData).to.be.equal([link21, link18].join(', '));
    });
});
