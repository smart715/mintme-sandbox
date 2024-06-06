import {shallowMount, createLocalVue} from '@vue/test-utils';
import Feed from '../../js/components/Feed';
import {WSAPI} from '../../js/utils/constants';
import Decimal from 'decimal.js';
import cryptoModule from '../../js/storage/modules/crypto';
import Vuex from 'vuex';

Object.defineProperty(window, 'EventSource', {
    value: jest.fn(),
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

const testActivity = {
    type: 0,
    createdAt: '2021-03-02T14:07:06-04:00',
    context: {
        token: 'Foo',
    },
};
const testDonationActivity = {
    type: WSAPI.order.type.DONATION,
    createdAt: '2021-03-02T14:07:06-04:00',
    context: {
        amount: new Decimal(1).toFixed(),
        token: 'moonpark',
        tradeIconUrl: 'WEB',
    },
};

const testTokenTradedActivity = {
    type: WSAPI.order.type.TOKEN_TRADED,
    createdAt: '2021-03-02T14:07:06-04:00',
    context: {
        amount: new Decimal(1).toFixed(),
        token: 'moonpark',
        buyer: 'buyer',
        tradeIconUrl: 'test',
    },
};

/**
 * @param {object} object
 * @return {object}
 */
function deepObjectCloning(object) {
    return JSON.parse(JSON.stringify(object));
}

/**
 * @return {object}
 */
function getStore() {
    return new Vuex.Store({
        modules: {
            crypto: {
                ...cryptoModule,
                state: {
                    cryptosMap: {},
                },
            },
        },
    });
}

describe('Feed', () => {
    it('should compute showFeed correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Feed, {
            localVue,
            propsData: {
                itemsProp: [],
                min: 0,
                max: 1,
            },
            store: getStore(),
        });

        expect(wrapper.vm.showFeed).toBe(false);

        wrapper.setData({items: [testActivity]});

        expect(wrapper.vm.showFeed).toBe(true);
    });

    it('should compute itemsToShow correctly and render rows correctly', async () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Feed, {
            localVue,
            propsData: {
                itemsProp: [testActivity],
                min: 6,
                max: 30,
            },
            store: getStore(),
        });

        expect(wrapper.vm.itemsToShow).toBe(1);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(1);

        await wrapper.setData({showMoreFeedPage: true});

        expect(wrapper.vm.itemsToShow).toBe(1);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(1);

        await wrapper.setData({
            showMoreFeedPage: false,
            items: new Array(7).fill(testActivity, 0, 7),
        });

        expect(wrapper.vm.itemsToShow).toBe(6);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(6);

        await wrapper.setData({showMoreFeedPage: true});

        expect(wrapper.vm.itemsToShow).toBe(7);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(7);

        await wrapper.setData({
            showMoreFeedPage: false,
            items: new Array(32).fill(testActivity, 0, 32),
        });

        expect(wrapper.vm.itemsToShow).toBe(6);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(6);

        await wrapper.setData({showMoreFeedPage: true});

        expect(wrapper.vm.itemsToShow).toBe(30);
        expect(wrapper.findAll('.padding-feed-cell').length).toBe(30);
    });

    it('should compute groupedItems correctly', () => {
        const expectedActivities = [
            deepObjectCloning(testActivity),
            deepObjectCloning(testTokenTradedActivity),
            deepObjectCloning(testDonationActivity),
            deepObjectCloning(testTokenTradedActivity),
            deepObjectCloning(testDonationActivity),
            deepObjectCloning(testActivity),
            deepObjectCloning(testDonationActivity),
            deepObjectCloning(testTokenTradedActivity),
            deepObjectCloning(testActivity),
            deepObjectCloning(testActivity),
        ];

        expectedActivities[1].context.amount = new Decimal(3).toFixed();
        expectedActivities[2].context.amount = new Decimal(2).toFixed();

        const localVue = mockVue();
        const wrapper = shallowMount(Feed, {
            localVue,
            propsData: {
                itemsProp: [
                    deepObjectCloning(testActivity),
                    deepObjectCloning(testTokenTradedActivity),
                    deepObjectCloning(testTokenTradedActivity),
                    deepObjectCloning(testTokenTradedActivity),
                    deepObjectCloning(testDonationActivity),
                    deepObjectCloning(testDonationActivity),
                    deepObjectCloning(testTokenTradedActivity),
                    deepObjectCloning(testDonationActivity),
                    deepObjectCloning(testActivity),
                    deepObjectCloning(testDonationActivity),
                    deepObjectCloning(testTokenTradedActivity),
                    deepObjectCloning(testActivity),
                    deepObjectCloning(testActivity),
                ],
                min: 6,
                max: 30,
            },
            store: getStore(),
        });
        expect(wrapper.vm.groupedItems).toStrictEqual(expectedActivities);
    });
});
