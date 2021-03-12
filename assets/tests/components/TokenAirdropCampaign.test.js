import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';
import Vuelidate from 'vuelidate';

delete window.location;
window.location = {
    reload: jest.fn(),
    href: '',
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('b-toggle', {});
    localVue.component('font-awesome-icon', {});
    localVue.component('b-collapse', {});
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @return {Store<axios>}
 */
function getStore() {
    return new Vuex.Store({
        modules: {tokenStatistics},
    });
}

const airdropParams = {
    min_tokens_amount: 0.01,
    min_participants_amount: 100,
    max_participants_amount: 999999,
    min_token_reward: 0.0001,
};

describe('TokenAirdropCampaign', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should check if airdrop campaign exists', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    airdropCampaignId: null,
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.hasAirdropCampaign).toBe(false);
        wrapper.vm.airdropCampaignId = 2;
        expect(wrapper.vm.hasAirdropCampaign).toBe(true);
    });

    it('should check if disable save button', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: true,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.tokenBalance = 800;
        wrapper.vm.tokensAmount = 0;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.tokensAmount = 50;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.participantsAmount = 99;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.btnDisabled).toBe(false);
        wrapper.vm.balanceLoaded = true;
        wrapper.vm.tokenBalance = 0;
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.vm.insufficientBalance).toBe(true);
        wrapper.vm.tokenBalance = 0.01;
        wrapper.vm.tokensAmount = 0.01;
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.vm.insufficientBalance).toBe(true);
        wrapper.vm.tokenBalance = 0.0101;
        wrapper.vm.tokensAmount = 0.01;
        expect(wrapper.vm.btnDisabled).toBe(false);
        expect(wrapper.vm.insufficientBalance).toBe(false);
    });

    it('check amount of tokens is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.tokensAmount = '0.0009';
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.tokensAmount = 100;
        expect(wrapper.vm.isAmountValid).toBe(true);
    });

    it('check amount of participants is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.isParticipantsAmountValid).toBe(false);
        wrapper.vm.participantsAmount = 99;
        expect(wrapper.vm.isParticipantsAmountValid).toBe(false);
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.isParticipantsAmountValid).toBe(true);
        wrapper.vm.participantsAmount = 1000000;
        expect(wrapper.vm.isParticipantsAmountValid).toBe(false);
        wrapper.vm.participantsAmount = 999999;
        expect(wrapper.vm.isParticipantsAmountValid).toBe(true);
    });

    it('check if end date is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        wrapper.vm.showEndDate = true;
        expect(wrapper.vm.isDateValid).toBe(true);
        expect(wrapper.vm.isDateEndValid).toBe(true);

        wrapper.vm.showEndDate = true;
        wrapper.vm.endDate = '2020-12-10 222';
        expect(wrapper.vm.isDateValid).toBe(false);
        expect(wrapper.vm.isDateEndValid).toBe(false);

        wrapper.vm.showEndDate = false;
        wrapper.vm.endDate = '2020-12-10 222';
        expect(wrapper.vm.isDateValid).toBe(false);
        expect(wrapper.vm.isDateEndValid).toBe(true);
    });

    it('check if airdrop campaign reward is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.isRewardValid).toBe(false);

        wrapper.vm.tokenBalance = 150;
        wrapper.vm.tokensAmount = 100;
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.isRewardValid).toBe(true);

        wrapper.vm.tokensAmount = '0.01';
        expect(wrapper.vm.isRewardValid).toBe(true);

        wrapper.vm.participantsAmount = 200;
        expect(wrapper.vm.isRewardValid).toBe(false);
    });

    it('can load token balance', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                tokenName: 'test2',
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
            },
        });

        moxios.stubRequest('token_exchange_amount', {
            status: 200,
            response: 1254.2356,
        });
        expect(wrapper.vm.balanceLoaded).toBe(false);

        moxios.wait(() => {
            expect(wrapper.vm.tokenBalance).toBe(1254.2356);
            expect(wrapper.vm.balanceLoaded).toBe(true);
            done();
        });
    });

    it('can load token ongoing airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                tokenName: 'test2',
                airdropParams,
            },
            data() {
                return {
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadTokenBalance: () => {},
            },
        });

        moxios.stubRequest('get_airdrop_campaign', {
            status: 200,
            response: {
                airdrop: {
                    id: 4,
                },
            },
        });

        expect(wrapper.vm.loading).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.hasAirdropCampaign).toBe(true);
            expect(wrapper.vm.airdropCampaignId).toBe(4);
            expect(wrapper.vm.loading).toBe(false);
            done();
        });
    });

    it('can create new token airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                tokenName: 'test2',
                airdropParams,
            },
            data() {
                return {
                    airdropCampaignId: null,
                    tokenBalance: 500,
                    tokensAmount: '100',
                    participantsAmount: 100,
                    showEndDate: false,
                    actions: {
                        twitterMessage: true,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        moxios.stubRequest('create_airdrop_campaign', {
            status: 200,
            response: {
                id: 7,
            },
        });

        wrapper.vm.createAirdropCampaign();
        expect(wrapper.vm.loading).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaignId).toBe(7);
            expect(wrapper.vm.loading).toBe(false);
            done();
        });
    });

    it('should show error message if airdrop reward less than 0.0001', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                tokenName: 'test5',
                airdropParams,
            },
            data() {
                return {
                    tokenBalance: 50,
                    tokensAmount: '0.015',
                    participantsAmount: 196,
                    showEndDate: false,
                    actions: {
                        twitterMessage: true,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        expect(wrapper.vm.errorMessage).toBe('');
        wrapper.vm.createAirdropCampaign();

        expect(wrapper.vm.btnDisabled).toBe(false);
        expect(wrapper.vm.isRewardValid).toBe(false);
        expect(wrapper.vm.errorMessage).toBe('airdrop.error_message');
    });

    it('can delete ongoing airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    airdropCampaignId: 3,
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        moxios.stubRequest('delete_airdrop_campaign', {
            status: 204,
        });

        wrapper.vm.deleteAirdropCampaign();
        expect(wrapper.vm.loading).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaignId).toBe(null);
            done();
        });
    });

    it('should set default amount of tokens and participants', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            store: getStore(),
            localVue,
            propsData: {
                airdropParams,
            },
            data() {
                return {
                    tokensAmount: 500,
                    participantsAmount: 170,
                    actions: {
                        twitterMessage: false,
                        twitterRetweet: false,
                        facebookMessage: false,
                        facebookPage: false,
                        facebookPost: false,
                        linkedinMessage: false,
                        youtubeSubscribe: false,
                        postLink: false,
                    },
                };
            },
            methods: {
                loadAirdropCampaign: () => {},
                loadTokenBalance: () => {},
            },
        });

        wrapper.vm.setDefaultValues();
        wrapper.vm.showEndDate = true;

        expect(wrapper.vm.tokensAmount).toBe(100);
        expect(wrapper.vm.participantsAmount).toBe(100);
        expect(wrapper.vm.isDateValid).toBe(true);
    });
});
