import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('font-awesome-icon', {});
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

        expect(wrapper.vm.actualParticipants).toBe(11);
        wrapper.vm.airdropCampaign.actualParticipants = 0;
        expect(wrapper.vm.actualParticipants).toBe(0);
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

        expect(wrapper.vm.airdropReward).toBe(0);
        wrapper.vm.loaded = true;
        expect(wrapper.vm.airdropReward).toBe('3');
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

        expect(wrapper.vm.endsDate).toBe(moment(dateNow).format('Do MMMM YYYY'));
        expect(wrapper.vm.endsTime).toBe(moment(dateNow).format('HH:mm'));
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

        expect(wrapper.vm.confirmButtonText).toBe('Log In');
        wrapper.setProps({loggedIn: true});
        expect(wrapper.vm.confirmButtonText).toBe('');
        wrapper.setProps({isOwner: true});
        expect(wrapper.vm.confirmButtonText).toBe('OK');
        wrapper.setProps({isOwner: false});
        wrapper.vm.alreadyClaimed = true;
        expect(wrapper.vm.confirmButtonText).toBe('OK');
    });

    it('should show confirm modal message properly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                loggedIn: false,
                isOwner: false,
                tokenName: 'test77',
            },
            data() {
                return {
                    loaded: true,
                    alreadyClaimed: false,
                    airdropCampaign: {
                        'amount': '300',
                        'participants': 100,
                    },
                };
            },
        });

        expect(wrapper.vm.confirmModalMessage).toBe('You have to be logged in to claim 3 test77.');
        wrapper.setProps({loggedIn: true});
        wrapper.setProps({isOwner: true});
        expect(wrapper.vm.confirmModalMessage).toBe('Sorry, you can\'t participate in your own airdrop.');
        wrapper.setProps({isOwner: false});
        wrapper.vm.alreadyClaimed = true;
        expect(wrapper.vm.confirmModalMessage).toBe('You already claimed tokens from this airdrop.');
        wrapper.setProps({isOwner: false});
        wrapper.vm.alreadyClaimed = false;
        expect(wrapper.vm.confirmModalMessage).toBe('Are you sure you want to claim 3 test77?');
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

        expect(wrapper.vm.showEndDate).toBe(true);
        wrapper.vm.airdropCampaign.endDate = '';
        expect(wrapper.vm.showEndDate).toBe(false);
    });

    it('should load airdrop campaign data', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
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
            expect(wrapper.vm.airdropCampaign.amount).toBe('568');
            expect(wrapper.vm.airdropCampaign.participants).toBe(120);
            expect(wrapper.vm.airdropCampaign.actualParticipants).toBe(8);
            expect(wrapper.vm.loaded).toBe(true);
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
            data() {
                return {
                    airdropCampaign: {
                        'id': 5,
                    },
                };
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
        expect(wrapper.vm.btnDisabled).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.actualParticipants).toBe(14);
            expect(wrapper.vm.alreadyClaimed).toBe(true);
            expect(wrapper.vm.btnDisabled).toBe(false);
            done();
        });
    });
});
