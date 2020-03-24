import {createLocalVue, mount, shallowMount} from '@vue/test-utils';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
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

describe('TokenOngoingAirdropCampaign', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should return airdrop actual participants amount', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            data() {
                return {
                    airdropCampaign: {
                        'amount': '125.2365',
                        'participants': 110,
                        'actualParticipants': 11,
                    },
                };
            },
        });

        expect(wrapper.vm.actualParticipants).to.equal(11);
        wrapper.vm.airdropCampaign.actualParticipants = 0;
        expect(wrapper.vm.actualParticipants).to.equal(0);
    });

    it('should return airdrop reward', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            data() {
                return {
                    airdropCampaign: {
                        'amount': '450',
                        'participants': 150,
                    },
                };
            },
        });

        expect(wrapper.vm.airdropReward).to.equal(0);
        wrapper.vm.loaded = true;
        expect(wrapper.vm.airdropReward).to.equal('3');
    });

    it('should check airdrop end date', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            data() {
                return {
                    airdropCampaign: {
                        'endDate': new Date(),
                    },
                };
            },
        });

        expect(wrapper.vm.showEndDate).to.be.true;
        wrapper.vm.airdropCampaign.endDate = '';
        expect(wrapper.vm.showEndDate).to.be.false;
    });

    it('should load airdrop campaign data', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                tokenName: 'test1',
            },
            data() {
                return {
                    airdropCampaign: {
                        'amount': '568',
                        'participants': 120,
                        'actualParticipants': 8,
                    },
                };
            },
        });

        wrapper.vm.getAirdropCampaign();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: {
                    data: {
                        'amount': '568',
                        'participants': 120,
                        'actualParticipants': 8,
                    },
                },
            }).then(() => done());
        });
    });

    it('can can claim airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                tokenName: 'test1',
                userAlreadyClaimed: false,
            },
        });

        wrapper.vm.claimAirdropCampaign();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 202,
            }).then(() => done());
        });
    });
});
