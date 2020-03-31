import {createLocalVue, mount, shallowMount} from '@vue/test-utils';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';

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

    it('should format airdrop end date/time properly', () => {
        let dateNow = moment();
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            data() {
                return {
                    airdropCampaign: {
                        endDate: dateNow,
                    },
                };
            },
        });

        expect(wrapper.vm.endsDate).to.equal(moment(dateNow).format('D MMMM YYYY'));
        expect(wrapper.vm.endsTime).to.equal(moment(dateNow).format('HH:mm'));
    });

    it('should show confirm button text properly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                loggedIn: false,
                isOwner: false,
            },
            data() {
                return {
                    alreadyClaimed: false,
                };
            },
        });

        expect(wrapper.vm.confirmButtonText).to.equal('Log In');
        wrapper.vm.loggedIn = true;
        expect(wrapper.vm.confirmButtonText).to.equal('');
        wrapper.vm.isOwner = true;
        expect(wrapper.vm.confirmButtonText).to.equal('OK');
        wrapper.vm.isOwner = false;
        wrapper.vm.alreadyClaimed = true;
        expect(wrapper.vm.confirmButtonText).to.equal('OK');
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
                    airdropCampaign: null,
                };
            },
        });

        moxios.stubRequest('get_airdrop_campaign', {
            status: 200,
            response: {
                'amount': '568',
                'participants': 120,
                'actualParticipants': 8,
            },
        });

        wrapper.vm.getAirdropCampaign();

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.amount).to.equal('568');
            expect(wrapper.vm.airdropCampaign.participants).to.equal(120);
            expect(wrapper.vm.airdropCampaign.actualParticipants).to.equal(8);
            expect(wrapper.vm.loaded).to.be.true;
            done();
        });
    });

    it('can can claim airdrop campaign', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                loggedIn: true,
                tokenName: 'test1',
                userAlreadyClaimed: false,
            },
        });

        moxios.stubRequest('claim_airdrop_campaign', {
            status: 200,
        });

        wrapper.vm.airdropCampaign = {
            amount: 300,
            participants: 100,
            actualParticipants: 13,
        };

        wrapper.vm.modalOnConfirm();
        expect(wrapper.vm.btnDisabled).to.be.true;

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.actualParticipants).to.equal(14);
            expect(wrapper.vm.alreadyClaimed).to.be.true;
            expect(wrapper.vm.btnDisabled).to.be.false;
            done();
        });
    });
});
