import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    localVue.use(Vuelidate);

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
            methods: {
                loadYoutubeClient: () => {},
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
                        reward: '3',
                    },
                };
            },
            methods: {
                loadYoutubeClient: () => {},
            },
        });

        expect(wrapper.vm.airdropReward).toBe(0);
        expect(wrapper.vm.halfReward).toBe(0);
        wrapper.vm.loaded = true;
        expect(wrapper.vm.airdropReward).toBe('3');
        expect(wrapper.vm.halfReward).toBe('1.5');
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
            methods: {
                loadYoutubeClient: () => {},
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
            methods: {
                loadYoutubeClient: () => {},
            },
        });
        expect(wrapper.vm.confirmButtonText).toBe('');
        wrapper.setProps({isOwner: true});
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
                        amount: '300',
                        participants: 100,
                        reward: '3',
                    },
                };
            },
            methods: {
                loadYoutubeClient: () => {},
            },
        });
        wrapper.setProps({isOwner: true});
        expect(wrapper.vm.confirmModalMessage).toBe('ongoing_airdrop.confirm_message.cant_participate');
        wrapper.setProps({isOwner: false});
        wrapper.vm.alreadyClaimed = false;
        expect(wrapper.vm.confirmModalMessage).toBe('ongoing_airdrop.confirm_message');
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
            methods: {
                loadYoutubeClient: () => {},
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
            methods: {
                loadYoutubeClient: () => {},
            },
        });

        moxios.stubRequest('get_airdrop_campaign', {
            status: 200,
            response: {
                airdrop: {
                    amount: '568',
                    participants: 120,
                    actualParticipants: 8,
                    reward: '4',
                },
                referral_code: null,
            },
        });

        wrapper.vm.getAirdropCampaign();

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.amount).toBe('568');
            expect(wrapper.vm.airdropCampaign.participants).toBe(120);
            expect(wrapper.vm.airdropCampaign.actualParticipants).toBe(8);
            expect(wrapper.vm.airdropCampaign.reward).toBe('4');
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
            methods: {
                loadYoutubeClient: () => {},
            },
        });

        moxios.stubRequest('claim_airdrop_campaign', {
            status: 200,
            response: {
                data: {},
            },
        });

        wrapper.vm.airdropCampaign = {
            amount: 300,
            participants: 100,
            actualParticipants: 13,
        };

        wrapper.vm.modalOnConfirm();
        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.actualParticipants).toBe(14);
            expect(wrapper.vm.alreadyClaimed).toBe(true);
            done();
        });
    });

    it('can store airdrop action for guest user', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
            localVue,
            propsData: {
                loggedIn: false,
                tokenName: 'test77',
                isOwner: false,
            },
            data() {
                return {
                    alreadyClaimed: false,
                    airdropCampaign: {
                        amount: '300',
                        participants: 100,
                        id: 5,
                        reward: '3',
                        actions: {
                            facebookPage: {
                                id: 1,
                                done: false,
                            },
                        },
                    },
                };
            },
            methods: {
                loadYoutubeClient: () => {},
            },
        });
        moxios.stubRequest('claim_airdrop_action_for_guest_user', {
            status: 200,
            response: {
                data: null,
            },
        });

        wrapper.vm.claimAction(wrapper.vm.airdropCampaign.actions.facebookPage);

        moxios.wait(() => {
            expect(wrapper.vm.airdropCampaign.actions.facebookPage.done).toBe(true);
            done();
        });
    });
});
