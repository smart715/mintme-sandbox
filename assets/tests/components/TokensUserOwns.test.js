import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokensUserOwns from '../../js/components/profile/TokensUserOwns';
import axios from 'axios';
import moxios from 'moxios';

const nicknameTest = 'nicknameTest';

const cryptoTest = {
    WEB: {
        image: {
            avatar_small: '',
        },
    },
};

const tooltipConfigTest = {
    title: nicknameTest,
    boundary: 'viewport',
    variant: 'light',
    disabled: true,
};

const tokenTest = {
    cryptoSymbol: 'WEB',
    decimals: 12,
    deploymentStatus: 'not-deployed',
    identifier: 'TOK000000000001',
    image: {
        avatar_small: '',
    },
    name: 'tokenTest',
    subunit: 4,
    symbol: 'tokenTest',
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTokensUserOwns(props = {}) {
    return shallowMount(TokensUserOwns, {
        localVue: mockVue(),
        stubs: ['b-tooltip', 'font-awesome-icon'],
        sync: false,
        propsData: {
            nickname: nicknameTest,
            cryptos: cryptoTest,
            profileOwner: true,
            tokensCount: 1,
            tokensUserOwnsProp: [tokenTest],
            ...props,
        },
    });
}

describe('TokensUserOwns', () => {
    const url = '/token';

    Object.defineProperty(window, 'location', {
        value: {
            href: url,
        },
        configurable: true,
    });


    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('showSeeMoreButton', () => {
        it('should return true if there is next page', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({
                tokensCount: 50,
            });

            await wrapper.setData({
                currentPage: 2,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(true);
        });

        it('should return false if there is no tokens', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({
                tokensCount: 0,
            });

            await wrapper.setData({
                currentPage: 1,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should return false if there is no next page', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({
                tokensCount: 10,
            });

            await wrapper.setData({
                currentPage: 2,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });
    });

    it('Verify that "translationsContext" works correctly', async () => {
        const wrapper = mockTokensUserOwns();

        expect(wrapper.vm.translationsContext).toStrictEqual({'nickname': 'nicknameTest'});

        await wrapper.setData({
            maxLengthToTruncate: 10,
        });

        expect(wrapper.vm.translationsContext).toStrictEqual({'nickname': 'nicknameTe...'});
    });

    it('Verify that "rowClickHandler" works correctly', () => {
        const record = {
            url: 'url_record',
        };

        const wrapper = mockTokensUserOwns();

        wrapper.vm.rowClickHandler(record);

        expect(window.location.href).toBe('url_record');
    });

    it('Verify that "profileOwnerTooltipConfig" works correctly', () => {
        const wrapper = mockTokensUserOwns();

        expect(wrapper.vm.profileOwnerTooltipConfig).toEqual(tooltipConfigTest);
    });

    it('Verify that "showSeeMoreButton" works correctly', async () => {
        const wrapper = mockTokensUserOwns();

        await wrapper.setData({
            perPage: 1,
            currentPage: 1,
        });

        expect(wrapper.vm.showSeeMoreButton).toBe(false);

        await wrapper.setProps({
            tokensCount: 2,
        });

        expect(wrapper.vm.showSeeMoreButton).toBe(true);
    });

    it('Verify that "updateTableData" works correctly', async (done) => {
        const wrapper = mockTokensUserOwns();

        await wrapper.setData({
            perPage: 10,
            currentPage: 1,
        });

        moxios.stubRequest('tokens_user_owns', {
            status: 200,
            response: [tokenTest],
        });

        wrapper.vm.updateTableData();

        moxios.wait(() => {
            expect(wrapper.vm.tableData).toEqual([tokenTest]);
            expect(wrapper.vm.currentPage).toBe(2);
            done();
        });
    });

    describe('showSeeMoreButton', () => {
        it('should return tue if there is next page', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({tokensCount: 50});

            await wrapper.setData({
                currentPage: 2,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(true);
        });

        it('should return tue if there is no tokens', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({tokensCount: 0});

            await wrapper.setData({
                currentPage: 1,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });

        it('should return tue if there is no next page', async () => {
            const wrapper = mockTokensUserOwns();

            await wrapper.setProps({
                tokensCount: 10,
            });

            await wrapper.setData({
                currentPage: 1,
                perPage: 10,
            });

            expect(wrapper.vm.showSeeMoreButton).toBe(false);
        });
    });
});
