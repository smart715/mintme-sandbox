import PromotionHistory from '../../js/components/wallet/PromotionHistory';
import axios from 'axios';
import moxios from 'moxios';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import user from '../../js/storage/modules/user';
import {
    AIRDROP,
    BOUNTY,
    SHARE_POST,
    TOKEN_SHOP,
} from '../../js/utils/constants';
import moment from 'moment';

const $routing = {
    generate: (val, params) => {
        return val
            + (params.name ? '/' + params.name : '')
            + (params.nickname ? '/' + params.nickname : '');
    },
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    const $store = new Vuex.Store({
        modules: {user},
    });
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = $routing;
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$store = $store;
            Vue.prototype.$sortCompare = () => {};
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });


    return localVue;
}

const tableDataRow = {
    amount: '1.000000000000',
    createdAt: 1652960007,
    status: 'completed',
    token: {
        name: 'moonpark',
        ownerId: 1,
    },
    type: TOKEN_SHOP,
    user: {
        profile: {
            nickname: 'bender',
            image: {
                avatar_small: 'https://localhost/media/cache/resolve/avatar_small/media/default_profile.png',
            },
        },
    },
};

const tableData = [
    {...tableDataRow},
    {...tableDataRow},
    {...tableDataRow},
    {...tableDataRow},
    {...tableDataRow},
    {...tableDataRow},
];

describe('PromotionHistory', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('updateTableData', () => {
        it(
            `should do $axios request and set tableData and currentPage correctly
            when result of $axios request is empty`,
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(PromotionHistory, {
                    localVue,
                });

                moxios.stubRequest('promotion_history', {
                    status: 200,
                    response: [],
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual([]);
                    expect(wrapper.vm.currentPage).toBe(0);
                    done();
                });
            }
        );

        it(
            `should do $axios request and set tableData and currentPage correctly
            when result of $axios request is not empty`,
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(PromotionHistory, {
                    localVue,
                });

                moxios.stubRequest('promotion_history', {
                    status: 200,
                    response: tableData,
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual([...tableData]);
                    expect(wrapper.vm.currentPage).toBe(1);
                    done();
                });
            }
        );

        it(
            `should set allHistoryLoaded = true
            when result of $axios request is less than perPage`,
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(PromotionHistory, {
                    localVue,
                });

                wrapper.vm.perPage = 11;
                wrapper.vm.allHistoryLoaded = false;

                moxios.stubRequest('promotion_history', {
                    status: 200,
                    response: tableData,
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.allHistoryLoaded).toBe(true);
                    done();
                });
            }
        );
    });

    describe('totalRows', () => {
        it('should calc rows properly', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });
            wrapper.vm.tableData = [
                {...tableDataRow},
                {...tableDataRow},
                {...tableDataRow},
            ];

            expect(wrapper.vm.totalRows).toBe(3);
        });

        it('should return 0 when tableData is null', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            expect(wrapper.vm.totalRows).toBe(0);
        });
    });

    describe('hasPromotionHistory', () => {
        it('should return true when table history has elements', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });
            wrapper.vm.tableData = [
                {...tableDataRow},
            ];

            expect(wrapper.vm.hasPromotionHistory).toBe(true);
        });

        it('should return false when table history has no elements', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });
            wrapper.vm.tableData = [];

            expect(wrapper.vm.hasPromotionHistory).toBe(false);
        });

        it('should return false when table history is null', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            expect(wrapper.vm.hasPromotionHistory).toBe(false);
        });
    });

    describe('loaded', () => {
        it('should return false if tableData is null', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            expect(wrapper.vm.loaded).toBe(false);
        });

        it('should return true if tableData is empty array', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });
            wrapper.vm.tableData = [];

            expect(wrapper.vm.loaded).toBe(true);
        });

        it('should return true if tableData is not empty array', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });
            wrapper.vm.tableData = [
                {...tableDataRow},
            ];

            expect(wrapper.vm.loaded).toBe(true);
        });
    });

    it('fieldsArray should properly transform field into array', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });

        const expectedFieldsArray = [
            {
                key: 'date',
                label: wrapper.vm.$t('wallet.promotion_history.table.date'),
                sortable: true,
                thStyle: {width: '10rem'},
            },
            {
                key: 'type',
                label: wrapper.vm.$t('wallet.promotion_history.table.type'),
                sortable: true,
                type: 'string',
                thStyle: {width: '10rem'},
            },
            {
                key: 'status',
                label: wrapper.vm.$t('wallet.promotion_history.table.status'),
                sortable: true,
                tdClass: 'text-capitalize',
                type: 'string',
                thStyle: {width: '10rem'},
            },
            {
                key: 'tokenName',
                label: wrapper.vm.$t('wallet.promotion_history.table.token_name'),
                sortable: true,
                type: 'string',
                thStyle: {width: '10rem'},
            },
            {
                key: 'amount',
                label: wrapper.vm.$t('wallet.promotion_history.table.amount'),
                sortable: true,
                type: 'numeric',
                tdClass: 'text-right',
                thClass: 'text-right sorting-arrows-th',
                thStyle: {width: '10rem'},
            },
            {
                key: 'transaction',
                label: wrapper.vm.$t('wallet.promotion_history.table.transaction'),
                sortable: true,
                type: 'string',
                thStyle: {'min-width': '10rem'},
            },
            {
                key: 'userName',
                label: wrapper.vm.$t('wallet.promotion_history.table.nickname'),
                sortable: true,
                type: 'string',
                thStyle: {width: '10rem'},
            },
        ];

        const fieldsArray = wrapper.vm.fieldsArray.map((item) => {
            if ('date' === item.key) {
                delete item.formatter;
            }

            return item;
        });

        expect(fieldsArray).toEqual(expectedFieldsArray);
    });

    it('history should return properly sanitized promotion history', () =>{
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });
        wrapper.vm.$store.commit('user/setId', 1);

        const expectedHistoryRow = {
            amount: '1',
            avatarUrl: 'https://localhost/media/cache/resolve/avatar_small/media/default_profile.png',
            date: 1652960007,
            profileUrl: 'profile-view/bender',
            tokenName: 'moonpark',
            status: 'reward.status.in_delivery',
            tokenUrl: 'token_show_intro/moonpark',
            transaction: 'wallet.promotion_history.table.token_shop',
            type: 'wallet.promotion_history.table.incoming',
            userName: 'bender',
        };
        const expectedHistory = [
            {...expectedHistoryRow},
            {...expectedHistoryRow},
        ];

        wrapper.vm.tableData = [
            {...tableDataRow},
            {...tableDataRow},
        ];

        expect(wrapper.vm.history).toEqual(expectedHistory);
    });

    it('nextPage should return properly value', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });

        expect(wrapper.vm.nextPage).toBe(1);

        wrapper.vm.currentPage++;
        expect(wrapper.vm.nextPage).toBe(2);
    });

    it('generateTokenUrl should return properly value', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });

        expect(wrapper.vm.generateTokenUrl('moonpark')).toBe('token_show_intro/moonpark');
    });

    it('generateProfileUrl should return properly value', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });

        expect(wrapper.vm.generateProfileUrl('bender')).toBe('profile-view/bender');
    });

    it('getTransactionTypeTrans should return properly value', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PromotionHistory, {
            localVue,
        });

        expect(wrapper.vm.getTransactionTypeTrans({type: AIRDROP}))
            .toBe('wallet.promotion_history.table.airdrop');
        expect(wrapper.vm.getTransactionTypeTrans({type: BOUNTY}))
            .toBe('wallet.promotion_history.table.bounty');
        expect(wrapper.vm.getTransactionTypeTrans({type: TOKEN_SHOP}))
            .toBe('wallet.promotion_history.table.token_shop');
        expect(wrapper.vm.getTransactionTypeTrans({type: SHARE_POST}))
            .toBe('wallet.promotion_history.table.share_post');
    });

    describe('getIncomingType', () => {
        it('should return propely value if it is token_shop', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            const transaction = {
                token: {
                    ownerId: 1,
                },
                type: TOKEN_SHOP,
            };

            wrapper.vm.$store.commit('user/setId', 1);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.incoming');

            wrapper.vm.$store.commit('user/setId', 2);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.outgoing');
        });

        it('should return propely value if it is bounty', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            const transaction = {
                token: {
                    ownerId: 1,
                },
                type: BOUNTY,
            };

            wrapper.vm.$store.commit('user/setId', 1);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.outgoing');

            wrapper.vm.$store.commit('user/setId', 2);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.incoming');
        });

        it('should return propely value if it is post share reward', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            const transaction = {
                token: {
                    ownerId: 1,
                },
                type: SHARE_POST,
            };

            wrapper.vm.$store.commit('user/setId', 1);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.outgoing');

            wrapper.vm.$store.commit('user/setId', 2);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.incoming');
        });

        it('should return propely value if it is airdrop', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            const transaction = {
                token: {
                    ownerId: 1,
                },
                type: AIRDROP,
            };

            wrapper.vm.$store.commit('user/setId', 1);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.outgoing');

            wrapper.vm.$store.commit('user/setId', 2);
            expect(wrapper.vm.getIncomingType(transaction)).toBe('wallet.promotion_history.table.incoming');
        });
    });

    describe('getDateFromTimestamp', () => {
        it('should return properly data if timestamp > 0', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            expect(wrapper.vm.getDateFromTimestamp(moment([2020, 4, 19, 11, 33, 0]).unix())).toBe('11:33 19 May');
        });

        it('shuld return "-" if timestamp <= 0', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(PromotionHistory, {
                localVue,
            });

            expect(wrapper.vm.getDateFromTimestamp(0)).toBe('-');
            expect(wrapper.vm.getDateFromTimestamp(-1293618723)).toBe('-');
        });
    });

    describe('showSeeMoreButton', () => {
        it('should be false when there is no loaded orders', () => {
            const wrapper = shallowMount(PromotionHistory, {
                localVue: mockVue(),
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should be true when full page is loaded', (done) => {
            const wrapper = shallowMount(PromotionHistory, {
                localVue: mockVue(),
            });
            wrapper.vm.perPage = 2;

            const fullPageResponse = [
                {...tableDataRow},
                {...tableDataRow},
            ];

            moxios.stubRequest('promotion_history', {
                status: 200,
                response: fullPageResponse,
            });

            wrapper.vm.updateTableData();

            moxios.wait(() => {
                expect(wrapper.vm.showSeeMoreButton).toBe(true);
                done();
            });
        });

        it('should be false when loading next page', () => {
            const wrapper = shallowMount(PromotionHistory, {
                localVue: mockVue(),
            });

            wrapper.vm.updateTableData();
            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should be false when loaded not full page (last page)', (done) => {
            moxios.wait(() => {
                const wrapper = shallowMount(PromotionHistory, {
                    localVue: mockVue(),
                });

                wrapper.vm.perPage = 2;

                const notFullPageResponse = [
                    {...tableDataRow},
                ];

                moxios.stubRequest('promotion_history', {
                    status: 200,
                    response: notFullPageResponse,
                });

                moxios.wait(() => {
                    expect(wrapper.vm.showSeeMoreButton).toBe(false);
                    done();
                });
            });
        });
    });
});
