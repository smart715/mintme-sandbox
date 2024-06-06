import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';
import Vuelidate from 'vuelidate';
import {NotificationMixin} from '../../js/mixins';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuex from 'vuex';

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
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    localVue.use(Vuelidate);
    localVue.use(Vuex);
    localVue.mixin(NotificationMixin);

    return localVue;
}

/**
 * @param {string} tokenName
 * @param {Object} modules
 * @return {Wrapper<Vuex.Store>}
 */
function mockStore(tokenName, modules) {
    const tradeBalanceMock = Object.assign({}, tradeBalance);

    tradeBalance.state.balances = {[tokenName]: {available: 0}};
    const store = new Vuex.Store({
        modules: {
            tradeBalance: tradeBalanceMock,
            user: {
                namespaced: true,
                getters: {
                    getIsSignedInWithTwitter: () => false,
                    getIsAuthorizedYoutube: () => false,
                },
            },
            ...modules,
        },
    });

    return store;
}

const airdropCampaignProp = {
    participants: 100,
    actions: {
        facebookMessage: {done: false, id: 2},
        linkedinMessage: {done: false, id: 3},
        postLink: {done: false, id: 4},
        twitterMessage: {done: false, id: 1},
        visitExternalUrl: {done: true, id: 5},
    },
    actionsData: {
        visitExternalUrl: 'https://google.com',
        twitterRetweet: 'retweet',
        youtubeSubscribe: 'channelName',
    },
    actualParticipants: 0,
    amount: '100.000000000000',
    endDate: null,
    id: 1,
    lockedAmount: '100.500000000000',
    reward: '1.000000000000',
    status: 1,
};

/**
 * @param {Object} modules
 * @return {Wrapper<Vue>}
 */
function createWrapper(modules = {}) {
    const localVue = mockVue();

    TokenOngoingAirdropCampaign.methods.openPopup = jest.fn();

    const wrapper = shallowMount(TokenOngoingAirdropCampaign, {
        store: mockStore('testToken', modules),
        localVue,
        propsData: {
            airdropCampaignProp,
        },
        directives: {
            'b-tooltip': {},
        },
    });

    return wrapper;
}

describe('TokenOngoingAirdropCampaign', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('translationsContext', () => {
        it('should return proper translations context', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({tokenName: 'VeryLongTokenNameVeryLongTokenName'});
            await wrapper.setData({
                airdropCampaign: {
                    reward: 3,
                    amount: '125.2365',
                    participants: 110,
                    actualParticipants: 11,
                },
            });

            expect(wrapper.vm.translationsContext.actualParticipants).toBe(11);
            expect(wrapper.vm.translationsContext.airdropReward).toBe('3');
            expect(wrapper.vm.translationsContext.tokenName).toBe('VeryLongTokenNameVeryLongToken...');
            expect(wrapper.vm.translationsContext.tokenAvatar).toContain('class="coin-avatar"');
        });
    });

    describe('actionsToServer', () => {
        it('should return proper actions to server', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.actionsToServer.twitterMessage.action.done).toBe(false);
            expect(wrapper.vm.actionsToServer.visitExternalUrl.action.done).toBe(true);
        });
    });

    describe('externalUrlTooltip', () => {
        it('should return proper external url tooltip', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.externalUrlTooltip.title).toBe('https://google.com');
        });
    });

    describe('airdropEndedText', () => {
        it('should return proper airdrop ended text', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.airdropEndedText).toBe('ongoing_airdrop.ended_embeded');
        });
    });

    describe('actionsLength', () => {
        it('should return proper actions length', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.actionsLength).toBe(5);
        });
    });

    describe('actualParticipants', () => {
        it('should return proper actual participants', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {actualParticipants: 6}});

            expect(wrapper.vm.actualParticipants).toBe(6);
        });
    });

    describe('airdropReward', () => {
        it('should return proper airdrop reward with formatted precision', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {reward: 123.12345678}});
            expect(wrapper.vm.airdropReward).toBe('123.1234');
        });

        it('should return 0 when loaded is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({loaded: false});
            expect(wrapper.vm.airdropReward).toBe(0);
        });
    });

    describe('halfReward', () => {
        it('should return proper half reward with formatted precision', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {reward: 123.12345678}});
            expect(wrapper.vm.halfReward).toBe('61.5617');
        });

        it('should return 0 when loaded is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({loaded: false});
            expect(wrapper.vm.halfReward).toBe(0);
        });
    });

    describe('showEndDate', () => {
        it('should return true when endDate is present', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {endDate: new Date()}});
            expect(wrapper.vm.showEndDate).toBe(true);
        });

        it('should return false when endDate is null', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {endDate: null}});
            expect(wrapper.vm.showEndDate).toBe(false);
        });
    });

    describe('endsDate', () => {
        it('should return proper ends date', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {endDate: new Date('2023-01-01')}});
            expect(wrapper.vm.endsDate).toBe('1st January 2023');
        });
    });

    describe('endsTime', () => {
        it('should return proper ends time', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {endDate: new Date('2023-01-01 12:00:00')}});
            expect(wrapper.vm.endsTime).toBe('12:00');
        });
    });

    describe('endsDateTime', () => {
        it('should return proper ends date time', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({airdropCampaign: {endDate: new Date('2023-01-01 12:00:00')}});
            expect(wrapper.vm.endsDateTime).toBe('1 January 2023 12:00:00');
        });
    });

    describe('confirmButtonText', () => {
        it('should return empty string by default', async () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.confirmButtonText).toBe('');
        });

        it('should return "OK" when isOwner is true or timeElapsed is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: true});
            expect(wrapper.vm.confirmButtonText).toBe('OK');

            await wrapper.setProps({isOwner: false});
            await wrapper.setData({timeElapsed: true});
            expect(wrapper.vm.confirmButtonText).toBe('OK');
        });
    });

    describe('confirmModalMessage', () => {
        it('should return proper message for owner when isOwner is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: true});
            expect(wrapper.vm.confirmModalMessage).toBe('ongoing_airdrop.confirm_message.cant_participate');
        });

        it('should return "ongoing_airdrop.ended" message when timeElapsed is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false});
            await wrapper.setData({timeElapsed: true});
            expect(wrapper.vm.confirmModalMessage).toBe('ongoing_airdrop.ended');
        });

        it(`should return "ongoing_airdrop.actions_message" when isOwner is
            false and timeElapsed is false`, async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false});
            await wrapper.setData({timeElapsed: false});
            expect(wrapper.vm.confirmModalMessage).toBe('ongoing_airdrop.actions_message');
        });
    });

    describe('actionMessage', () => {
        it('should return proper action message', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.actionMessage).toBe('ongoing_airdrop.actions.message');
        });
    });

    describe('twitterMessageLink', () => {
        it('should return proper twitter message link', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.twitterMessageLink).toBe(
                'https://twitter.com/intent/tweet?text=ongoing_airdrop.actions.message'
            );
        });
    });

    describe('twitterRetweetLink', () => {
        it('should return proper twitter retweet link', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.twitterRetweetLink).toBe('https://twitter.com/intent/retweet?tweet_id=retweet');
        });
    });

    describe('linkedinAuthLink', () => {
        it('should return proper linkedin auth link', async () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.linkedinAuthLink).toContain('https://www.linkedin.com/oauth/v2/authorization');
        });
    });

    describe('youtubeLink', () => {
        it('should return proper youtube link', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.youtubeLink).toBe('https://www.youtube.com/channel/channelName');
        });
    });

    describe('actionsCompleted', () => {
        it('should return false when actions are not completed', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.actionsCompleted).toBe(false);
        });

        it('should return true when actions are completed', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        facebookMessage: {done: true, id: 2},
                        linkedinMessage: {done: true, id: 3},
                        postLink: {done: true, id: 4},
                        twitterMessage: {done: true, id: 1},
                        visitExternalUrl: {done: true, id: 5},
                    },
                },
            });

            expect(wrapper.vm.actionsCompleted).toBe(true);
        });

        it('should return true when there are no actions', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {},
                },
            });

            expect(wrapper.vm.actionsCompleted).toBe(true);
        });
    });

    describe('referralLink', () => {
        it('should return proper referral link', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.referralLink).toBe('airdrop_referral');
        });
    });

    describe('modalTokenUrl', () => {
        it('should return proper modal token url', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.modalTokenUrl).toBe('token_show_intro');
        });
    });

    describe('postLinkUrlDisabled', () => {
        it('should be false when postLinkUrl is valid and not blacklisted', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'https://google.com'});
            await wrapper.setData({checkingBlackListedDomain: false, blackListedDomain: false});
            expect(wrapper.vm.postLinkUrlDisabled).toBe(false);
        });

        it('should be true when postLinkUrl is valid but blacklisted', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'https://google.com'});
            await wrapper.setData({checkingBlackListedDomain: false, blackListedDomain: true});
            expect(wrapper.vm.postLinkUrlDisabled).toBe(true);
        });

        it('should be true when postLinkUrl is invalid and blacklisted', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'invalid-url'});
            await wrapper.setData({checkingBlackListedDomain: false, blackListedDomain: true});
            expect(wrapper.vm.postLinkUrlDisabled).toBe(true);
        });

        it('should be true when postLinkUrl is invalid and not blacklisted', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'invalid-url'});
            await wrapper.setData({checkingBlackListedDomain: false, blackListedDomain: false});
            expect(wrapper.vm.postLinkUrlDisabled).toBe(true);
        });
    });

    describe('domainErrorMessage', () => {
        it('should return proper domain error message', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'google.com'});

            expect(wrapper.vm.domainErrorMessage).toBe('api.airdrop.url_start_with');
        });

        it('should return proper domain error message when blackListedDomain is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'https://google.com'});
            await wrapper.setData({blackListedDomain: true});

            expect(wrapper.vm.domainErrorMessage).toBe('api.airdrop.forbidden_domain');
        });

        it('should return empty string when there is no error', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({postLinkUrl: 'https://google.com'});

            expect(wrapper.vm.domainErrorMessage).toBe('');
        });
    });

    describe('buttonDisabled', () => {
        it('should return proper button disabled when actions are empty', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {},
                },
            });
            await wrapper.setProps({userAlreadyClaimed: false, isOwner: false});

            expect(wrapper.vm.buttonDisabled).toBe(false);
        });
    });

    describe('showCancelButton', () => {
        it('should return true when not owner, not embedded, not already claimed, and time not elapsed', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false, embeded: false});
            await wrapper.setData({alreadyClaimed: false, timeElapsed: false});
            expect(wrapper.vm.showCancelButton).toBe(true);
        });

        it('should return false when owner, not embedded, not already claimed, and time not elapsed', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: true, embeded: false});
            await wrapper.setData({alreadyClaimed: false, timeElapsed: false});
            expect(wrapper.vm.showCancelButton).toBe(false);
        });

        it('should return false when not owner, embedded, not already claimed, and time not elapsed', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false, embeded: true});
            await wrapper.setData({alreadyClaimed: false, timeElapsed: false});
            expect(wrapper.vm.showCancelButton).toBe(false);
        });

        it('should return false when not owner, not embedded, already claimed, and time not elapsed', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false, embeded: false});
            await wrapper.setData({alreadyClaimed: true, timeElapsed: false});
            expect(wrapper.vm.showCancelButton).toBe(false);
        });

        it('should return false when not owner, not embedded, not already claimed, and time elapsed', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false, embeded: false});
            await wrapper.setData({alreadyClaimed: false, timeElapsed: true});
            expect(wrapper.vm.showCancelButton).toBe(false);
        });
    });

    describe('showConfirmButton', () => {
        it('should return true when not already claimed, not in claim mode, and login tab not open', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({alreadyClaimed: false, claim: false, isLoginTabOpen: false});
            expect(wrapper.vm.showConfirmButton).toBe(true);
        });

        it('should return false when already claimed, not in claim mode, and login tab not open', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({alreadyClaimed: true, claim: false, isLoginTabOpen: false});
            expect(wrapper.vm.showConfirmButton).toBe(false);
        });

        it('should return false when not already claimed, in claim mode, and login tab not open', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({alreadyClaimed: false, claim: true, isLoginTabOpen: false});
            expect(wrapper.vm.showConfirmButton).toBe(false);
        });

        it('should return false when not already claimed, not in claim mode, and login tab open', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({alreadyClaimed: false, claim: false, isLoginTabOpen: true});
            expect(wrapper.vm.showConfirmButton).toBe(false);
        });
    });

    describe('airdropEndsMessage', () => {
        it('should return proper airdrop ends message', async () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.airdropEndsMessage).toBe('ongoing_airdrop.ends_sm.2');

            await wrapper.setData({
                airdropCampaign: {
                    endDate: new Date('2124-01-01 12:00:00'),
                },
            });

            expect(wrapper.vm.airdropEndsMessage).toBe('ongoing_airdrop.ends_sm.1');
        });
    });

    describe('durationValues', () => {
        it('should return proper duration values', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    endDate: new Date('2124-01-01 12:00:00'),
                },
            });

            expect(wrapper.vm.durationValues).toContain('year_acronym');
        });
    });

    describe('isLongUrl', () => {
        it('should return false for long url', () => {
            const wrapper = createWrapper();

            expect(wrapper.vm.isLongUrl).toBe(false);
        });

        it('should return true for long url', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actionsData: {
                        visitExternalUrl: 'https://veryLongUrlveryLongUrlveryLongUrlveryLongUrl.com',
                    },
                },
            });

            expect(wrapper.vm.isLongUrl).toBe(true);
        });
    });

    describe('externalUrlTruncated', () => {
        it('should return untruncated url', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actionsData: {
                        visitExternalUrl: 'https://google.com',
                    },
                },
            });

            expect(wrapper.vm.externalUrlTruncated).toBe('https://google.com');
        });

        it('should return truncated url', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actionsData: {
                        visitExternalUrl: 'https://veryLongUrlveryLongUrlveryLongUrlveryLongUrl.com',
                    },
                },
            });

            expect(wrapper.vm.externalUrlTruncated).toBe('https://veryLongUrlveryLongUrlveryL...');
        });
    });

    describe('postLinkUrl', () => {
        it('should return proper post link url', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                checkingBlackListedDomain: false,
                blackListedDomain: true,
                postLinkUrl: 'https://mintme.com',
            });

            expect(wrapper.vm.checkingBlackListedDomain).toBe(true);
            expect(wrapper.vm.blackListedDomain).toBe(false);
        });
    });

    describe('openLeaveSiteModal', () => {
        it('sets the visitExternalUrl and shows the leave site modal', () => {
            const wrapper = createWrapper();
            const event = {
                currentTarget: {
                    getAttribute: () => 'https://example.com',
                },
            };

            wrapper.vm.openLeaveSiteModal(event);

            expect(wrapper.vm.airdropCampaign.actionsData.visitExternalUrl).toBe('https://example.com');
            expect(wrapper.vm.showLeaveSiteModal).toBe(true);
        });
    });

    describe('leaveSite', () => {
        it('sets the visitExternalUrl and shows the leave site modal', async () => {
            window.open = jest.fn().mockReturnValue({
                closed: false,
            });
            const wrapper = createWrapper();
            const claimAction = jest.spyOn(wrapper.vm, 'claimAction');

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        visitExternalUrl: {done: true, id: 5},
                    },
                },
                showLeaveSiteModal: true,
            });
            wrapper.vm.leaveSite();

            expect(window.open).toHaveBeenCalledWith('https://example.com', '_blank');
            expect(claimAction).toHaveBeenCalledWith(wrapper.vm.actionsToServer.visitExternalUrl);
            expect(wrapper.vm.showLeaveSiteModal).toBe(false);
        });
    });

    describe('reloadFrame', () => {
        it('sets isReloadingFrame accurately', async () => {
            delete window.location;
            window.location = {
                reload: jest.fn(),
            };
            const wrapper = createWrapper();

            await wrapper.setData({isReloadingFrame: false});

            wrapper.vm.reloadFrame();

            expect(wrapper.vm.isReloadingFrame).toBe(true);
            expect(window.location.reload).toHaveBeenCalled();
        });
    });

    describe('showModalOnClick', () => {
        it('should set showModal to true when not owner', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: false});
            wrapper.vm.showModalOnClick();
            expect(wrapper.vm.showModal).toBe(true);
        });

        it('should set showModal to false when owner', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({isOwner: true});
            wrapper.vm.showModalOnClick();
            expect(wrapper.vm.showModal).toBe(false);
        });
    });

    describe('updateAirdropActionFromSession', () => {
        it('should not update airdropCampaign when response is not successfull', (done) => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('get_airdrop_completed_actions', {
                status: 500,
                response: null,
            });

            wrapper.vm.updateAirdropActionFromSession();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });

        it('should update airdropCampaign accurately when response is successfull', async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        facebookMessage: {done: false, id: 2},
                        linkedinMessage: {done: false, id: 3},
                        postLink: {done: false, id: 4},
                        twitterMessage: {done: false, id: 1},
                        visitExternalUrl: {done: true, id: 5},
                    },
                },
            });

            moxios.stubRequest('get_airdrop_completed_actions', {
                status: 200,
                response: [2, 3],
            });

            wrapper.vm.updateAirdropActionFromSession();

            moxios.wait(() => {
                expect(wrapper.vm.airdropCampaign.actions.facebookMessage.done).toBe(true);
                expect(wrapper.vm.airdropCampaign.actions.linkedinMessage.done).toBe(true);
                expect(wrapper.vm.airdropCampaign.actions.postLink.done).toBe(false);
                done();
            });
        });
    });

    describe('showCountdown', () => {
        it('should set timeElapsed to true and showDuration to false when duration is 0', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    reward: 0,
                    endDate: moment(),
                },
                timeElapsed: false,
                showDuration: true,
            });

            wrapper.vm.showCountdown();

            expect(wrapper.vm.timeElapsed).toBe(true);
            expect(wrapper.vm.showDuration).toBe(false);
        });
    });

    describe('countdownInterval', () => {
        it('should not call showCountdown imediately', () => {
            const wrapper = createWrapper();
            const showCountdown = jest.spyOn(wrapper.vm, 'showCountdown');

            wrapper.vm.countdownInterval();

            expect(showCountdown).not.toHaveBeenCalled();
        });
    });

    describe('getAirdropCampaign', () => {
        it('fetches the airdrop campaign and updates the component data', (done) => {
            const wrapper = createWrapper();

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

        it('calls notify error when request fail', (done) => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('get_airdrop_campaign', {
                status: 500,
            });

            wrapper.vm.getAirdropCampaign();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('closeModal', () => {
        it('should keep showModal true when embeded is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({showAirdropModal: true, embeded: true});
            await wrapper.setData({showModal: true});
            wrapper.vm.closeModal();
            expect(wrapper.vm.showModal).toBe(true);
        });

        it('should set showModal to false when embeded is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setProps({showAirdropModal: true, embeded: false});
            await wrapper.setData({showModal: true});
            wrapper.vm.closeModal();
            expect(wrapper.vm.showModal).toBe(false);
        });
    });

    describe('modalOnConfirm', () => {
        it('should call closeModal and set loginShowModal to true if user is not logged in', async () => {
            const wrapper = createWrapper();
            const closeModal = jest.spyOn(wrapper.vm, 'closeModal');

            await wrapper.setProps({loggedIn: false});
            await wrapper.setData({showModal: true});

            wrapper.vm.modalOnConfirm();

            expect(closeModal).toHaveBeenCalled();
            expect(wrapper.vm.loginShowModal).toBe(true);
        });

        it('should claimAction if user is logged in and response is successfull', async (done) => {
            const wrapper = createWrapper();
            const setQuoteFullBalance = jest.spyOn(wrapper.vm, 'setQuoteFullBalance');

            await wrapper.setProps({loggedIn: true, isOwner: false});
            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        facebookMessage: {done: true, id: 2},
                        linkedinMessage: {done: true, id: 3},
                        postLink: {done: true, id: 4},
                        twitterMessage: {done: true, id: 1},
                        visitExternalUrl: {done: true, id: 5},
                    },
                    participants: 100,
                    actualParticipants: 0,
                    reward: 0,
                },
            });

            moxios.stubRequest('claim_airdrop_campaign', {
                status: 200,
                response: {
                    balance: 300,
                },
            });

            wrapper.vm.modalOnConfirm();

            moxios.wait(() => {
                expect(wrapper.vm.airdropCampaign.actualParticipants).toBe(1);
                expect(setQuoteFullBalance).toHaveBeenCalled();
                expect(wrapper.vm.claim).toBe(false);
                expect(wrapper.vm.alreadyClaimed).toBe(true);
                done();
            });
        });

        it('should call notifyError if response is not successfull', async (done) => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        facebookMessage: {done: true, id: 2},
                    },
                    reward: 0,
                },
            });
            await wrapper.setProps({loggedIn: true, isOwner: false});

            moxios.stubRequest('claim_airdrop_campaign', {
                status: 500,
                response: {
                    message: 'error',
                },
            });

            wrapper.vm.modalOnConfirm();

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
                expect(wrapper.vm.claim).toBe(false);
                done();
            });
        });
    });

    describe('claimAction', () => {
        it('should set actionType done to true', () => {
            const wrapper = createWrapper();
            const actionType = {
                action: {
                    done: false,
                    id: 'action1',
                },
                route: 'route1',
                data: 'data1',
            };

            moxios.stubRequest('route1', {
                status: 200,
            });

            wrapper.vm.claimAction(actionType);

            moxios.wait(() => {
                expect(actionType.action.done).toBe(true);
            });
        });

        it('should call notifyError', () => {
            const wrapper = createWrapper();
            const actionType = {
                action: {
                    done: false,
                    id: 'action1',
                },
                route: 'route1',
                data: 'data1',
            };
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            moxios.stubRequest('route1', {
                status: 500,
            });

            wrapper.vm.claimAction(actionType);

            moxios.wait(() => {
                expect(notifyErrorSpy).toHaveBeenCalled();
            });
        });
    });

    describe('claimTwitterMessage', () => {
        it('should openPopup if twitterMessage is done', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterMessage: {done: true, id: 1},
                    },
                },
            });

            wrapper.vm.claimTwitterMessage();

            expect(window.open).toHaveBeenCalled();
        });

        it(`should call signInWithTwitter if twitterMessage
            is not done and user is not signed in with twitter`, async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterMessage: {done: false, id: 1},
                    },
                },
            });

            moxios.stubRequest('twitter_request_token', {
                status: 200,
                response: {
                    url: 'url',
                },
            });

            wrapper.vm.claimTwitterMessage();

            moxios.wait(() => {
                expect(window.open).toHaveBeenCalledWith('url', 'popup', 'width=600,height=600');
                done();
            });
        });

        it('should set showConfirmTwitterMessageModal to true if no condition is met', async () => {
            const wrapper = createWrapper({
                user: {
                    namespaced: true,
                    getters: {
                        getIsSignedInWithTwitter: () => true,
                    },
                },
            });

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterMessage: {done: false, id: 1},
                    },
                },
            });

            wrapper.vm.claimTwitterMessage();

            expect(wrapper.vm.showConfirmTwitterMessageModal).toBe(true);
        });
    });

    describe('twitterErrorHandler', () => {
        it('should call signInWithTwitter if twitter token is invalid', (done) => {
            const wrapper = createWrapper();

            moxios.stubRequest('twitter_request_token', {
                status: 200,
                response: {
                    url: 'url',
                },
            });

            wrapper.vm.twitterErrorHandler({
                response: {
                    data: {
                        message: 'invalid twitter token',
                    },
                },
            });

            moxios.wait(() => {
                expect(window.open).toHaveBeenCalledWith('url', 'popup', 'width=600,height=600');
                done();
            });
        });
    });

    describe('linkedinErrorHandler', () => {
        it('should call notifyError if error status is 422', () => {
            const wrapper = createWrapper();
            const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

            wrapper.vm.linkedinErrorHandler({
                response: {
                    status: 422,
                },
            });

            expect(notifyErrorSpy).toHaveBeenCalled();
        });
    });

    describe('claimTwitterRetweet', () => {
        it('should openPopup if twitterRetweet is done', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterRetweet: {done: true, id: 1},
                    },
                },
            });

            wrapper.vm.claimTwitterRetweet();

            expect(window.open).toHaveBeenCalledWith(
                'https://twitter.com/intent/retweet?tweet_id=retweet',
                'popup',
                'width=600,height=600'
            );
        });

        it('should call signInWithTwitter if twitterRetweet is not done', async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterRetweet: {done: false, id: 1},
                    },
                },
            });

            moxios.stubRequest('twitter_request_token', {
                status: 200,
                response: {
                    url: 'url',
                },
            });

            wrapper.vm.claimTwitterRetweet();

            moxios.wait(() => {
                expect(window.open).toHaveBeenCalledWith('url', 'popup', 'width=600,height=600');
                done();
            });
        });

        it('should call claimAction if no condition is met', async () => {
            const wrapper = createWrapper({
                user: {
                    namespaced: true,
                    getters: {
                        getIsSignedInWithTwitter: () => true,
                    },
                },
            });
            const claimAction = jest.spyOn(wrapper.vm, 'claimAction');

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        twitterRetweet: {done: false, id: 1},
                    },
                },
            });

            wrapper.vm.claimTwitterRetweet();

            expect(claimAction).toHaveBeenCalled();
        });
    });

    describe('openFacebookMessage', () => {
        it('should claimAction of facebookMessage', () => {
            const FB = {
                ui: jest.fn((options, callback) => callback([{}])),
            };
            global.FB = FB;
            const wrapper = createWrapper();
            const claimAction = jest.spyOn(wrapper.vm, 'claimAction');

            wrapper.vm.openFacebookMessage();

            expect(claimAction).toHaveBeenCalled();
        });
    });

    describe('claimLinkedin', () => {
        it('should set showConfirmLinkedinMessageModal to true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        linkedinMessage: {done: false, id: 3},
                    },
                },
            });
            wrapper.vm.claimLinkedin();

            expect(window.open).toHaveBeenCalled();
        });
    });

    describe('claimYoutube', () => {
        it('should call openPopup if youtubeSubscribe is done', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        youtubeSubscribe: {done: true, id: 3},
                    },
                },
            });

            wrapper.vm.claimYoutube();

            expect(window.open).toHaveBeenCalledWith(
                'https://www.youtube.com/channel/channelName',
                'popup',
                'width=600,height=600'
            );
        });

        it('should call authorizeYoutube if youtubeSubscribe is not done and !isAuthorizedYoutube', async (done) => {
            window.open = jest.fn().mockReturnValue({
                closed: false,
            });
            const wrapper = createWrapper();

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        youtubeSubscribe: {done: false, id: 1},
                    },
                },
            });

            moxios.stubRequest('youtube_request_token', {
                status: 200,
                response: {
                    url: 'url',
                },
            });

            wrapper.vm.claimYoutube();

            moxios.wait(() => {
                expect(window.open).toHaveBeenCalledWith('url', 'popup', 'width=600,height=600');
                done();
            });
        });

        it('should call claimAction if no condition is met', async () => {
            const wrapper = createWrapper({
                user: {
                    namespaced: true,
                    getters: {
                        getIsAuthorizedYoutube: () => true,
                    },
                },
            });
            const claimAction = jest.spyOn(wrapper.vm, 'claimAction');

            await wrapper.setData({
                airdropCampaign: {
                    actions: {
                        youtubeSubscribe: {done: false, id: 1},
                    },
                },
            });

            wrapper.vm.claimYoutube();

            expect(claimAction).toHaveBeenCalled();
        });
    });

    describe('subscribeYoutube', () => {
        it('should call openPopup', () => {
            const wrapper = createWrapper();

            wrapper.vm.subscribeYoutube();

            expect(window.open).toHaveBeenCalledWith(
                'https://www.youtube.com/channel/channelName',
                'popup',
                'width=600,height=600'
            );
        });
    });

    describe('checkBlacklistedDomain', () => {
        it('should set checkingBlackListedDomain and blackListedDomain to false if url is invalid', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({
                postLinkUrl: 'invalidUrl',
                checkingBlackListedDomain: true,
                blackListedDomain: true,
            });

            wrapper.vm.checkBlacklistedDomain();

            expect(wrapper.vm.checkingBlackListedDomain).toBe(false);
            expect(wrapper.vm.blackListedDomain).toBe(false);
        });

        it('should set correct properties if response is successfull', async (done) => {
            const wrapper = createWrapper();

            await wrapper.setData({
                postLinkUrl: 'https://google.com',
                checkingBlackListedDomain: true,
                blackListedDomain: false,
            });

            moxios.stubRequest('airdrop_domain_blacklist_check', {
                status: 200,
                response: {
                    blacklisted: true,
                },
            });

            wrapper.vm.checkBlacklistedDomain();

            moxios.wait(() => {
                expect(wrapper.vm.checkingBlackListedDomain).toBe(false);
                expect(wrapper.vm.blackListedDomain).toBe(true);
                done();
            });
        });
    });

    describe('checkStorageError', () => {
        it('should set storageError to true if window.localStorage fails', () => {
            Object.defineProperty(window, 'localStorage', {
                get: () => {
                    throw new Error();
                },
            });
            const wrapper = createWrapper();

            wrapper.vm.checkStorageError();

            expect(wrapper.vm.storageError).toBe(true);
        });

        it('should keep storageError false if window.localStorage doesn\'t fail', () => {
            Object.defineProperty(window, 'localStorage', {
                get: () => ({}),
            });
            const wrapper = createWrapper();

            wrapper.vm.checkStorageError();

            expect(wrapper.vm.storageError).toBe(false);
        });
    });

    describe('onPhoneVerified', () => {
        it('should set addPhoneModalVisible to false and call showModalOnClick', () => {
            const wrapper = createWrapper();
            const showModalOnClick = jest.spyOn(wrapper.vm, 'showModalOnClick');

            wrapper.vm.onPhoneVerified();

            expect(wrapper.vm.addPhoneModalVisible).toBe(false);
            expect(showModalOnClick).toHaveBeenCalled();
        });
    });
});
