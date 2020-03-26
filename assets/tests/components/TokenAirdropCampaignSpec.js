import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

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
            localVue,
            data() {
                return {
                    airdropCampaignId: null,
                };
            },
        });

        expect(wrapper.vm.hasAirdropCampaign).to.be.false;
        wrapper.vm.airdropCampaignId = 2;
        expect(wrapper.vm.hasAirdropCampaign).to.be.true;
    });

    it('should check if disable save button', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
        });

        expect(wrapper.vm.btnDisabled).to.be.true;
        wrapper.vm.tokenBalance = 800;
        wrapper.vm.tokensAmount = 0;
        expect(wrapper.vm.btnDisabled).to.be.true;
        wrapper.vm.tokensAmount = 50;
        expect(wrapper.vm.btnDisabled).to.be.true;
        wrapper.vm.participantsAmount = 99;
        expect(wrapper.vm.btnDisabled).to.be.true;
        wrapper.vm.participantsAmount = 101;
        expect(wrapper.vm.btnDisabled).to.be.false;
    });

    it('check amount of tokens is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
        });

        expect(wrapper.vm.isAmountValid).to.be.false;
        wrapper.vm.tokensAmount = '0.0009';
        expect(wrapper.vm.isAmountValid).to.be.false;
        wrapper.vm.tokensAmount = 100;
        expect(wrapper.vm.isAmountValid).to.be.false;
        wrapper.vm.tokenBalance = 101;
        expect(wrapper.vm.isAmountValid).to.be.true;
    });

    it('check amount of participants is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
        });

        expect(wrapper.vm.isParticipantsAmountValid).to.be.false;
        wrapper.vm.participantsAmount = 99;
        expect(wrapper.vm.isParticipantsAmountValid).to.be.false;
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.isParticipantsAmountValid).to.be.true;
    });

    it('check if end date is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
        });

        wrapper.vm.showEndDate = true;
        expect(wrapper.vm.isDateValid).to.be.true;
        expect(wrapper.vm.isDateEndValid).to.be.true;

        wrapper.vm.showEndDate = true;
        wrapper.vm.endDate = '2020-12-10 222';
        expect(wrapper.vm.isDateValid).to.be.false;
        expect(wrapper.vm.isDateEndValid).to.be.false;

        wrapper.vm.showEndDate = false;
        wrapper.vm.endDate = '2020-12-10 222';
        expect(wrapper.vm.isDateValid).to.be.false;
        expect(wrapper.vm.isDateEndValid).to.be.true;
    });

    it('check if airdrop campaign reward is valid', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
        });

        expect(wrapper.vm.isRewardValid).to.be.false;

        wrapper.vm.tokenBalance = 150;
        wrapper.vm.tokensAmount = 100;
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.isRewardValid).to.be.true;

        wrapper.vm.tokensAmount = '0.01';
        expect(wrapper.vm.isRewardValid).to.be.true;

        wrapper.vm.participantsAmount = 200;
        expect(wrapper.vm.isRewardValid).to.be.false;
    });

    it('can load token balance', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
            propsData: {
                tokenName: 'test2',
            },
        });

        wrapper.vm.loadTokenBalance();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: {
                    data: '100.000000',
                },
            }).then(() => done());
        });
    });

    it('can load token ongoing airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
            propsData: {
                tokenName: 'test2',
            },
        });

        wrapper.vm.loadAirdropCampaign();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: {
                    data: {
                        'id': 10,
                    },
                },
            }).then(() => done());
        });
    });

    it('can create new token airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
            propsData: {
                tokenName: 'test2',
            },
            data() {
                return {
                    tokensAmount: '100',
                    participantsAmount: 100,
                };
            },
        });

        wrapper.vm.createAirdropCampaign();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: {
                    data: {
                        'id': 10,
                    },
                },
            }).then(() => done());
        });
    });

    it('can delete ongoing airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
            data() {
                return {
                    airdropCampaignId: 3,
                };
            },
        });

        wrapper.vm.deleteAirdropCampaign();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 204,
            }).then(() => done());
        });
    });

    it('should set default amount of tokens and participants', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenAirdropCampaign, {
            localVue,
            data() {
                return {
                    tokensAmount: 500,
                    participantsAmount: 170,
                };
            },
        });

        wrapper.vm.setDefaultValues();
        wrapper.vm.showEndDate = true;

        expect(wrapper.vm.tokensAmount).to.be.equal(100);
        expect(wrapper.vm.participantsAmount).to.be.equal(100);
        expect(wrapper.vm.isDateValid).to.be.true;
    });
});
