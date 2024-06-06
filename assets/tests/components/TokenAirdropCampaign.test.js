import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenAirdropCampaign';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuelidate from 'vuelidate';
import moment from 'moment';

delete window.location;
window.location = {
    reload: jest.fn(),
    href: '',
};

/**
 * @return {Store<axios>}
 */
function getStore() {
    return new Vuex.Store({
        modules: {tradeBalance},
    });
}

const airdropParams = {
    min_tokens_amount: 0.01,
    min_participants_amount: 100,
    max_participants_amount: 999999,
    min_token_reward: 0.0001,
};

const directives = {
    'b-tooltip': {},
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper}
 */
function mockTokenAirdropCampaign(props = {}, data = {}) {
    const localVue = mockVue();
    return shallowMount(TokenAirdropCampaign, {
        store: getStore(),
        localVue,
        propsData: {
            airdropParams,
            ...props,
        },
        data() {
            return {
                ...data,
            };
        },
        directives: {
            ...directives,
        },
    });
}

/**
 * @param {Wrapper} wrapper
 * @param {number} amount
 */
function setBalance(wrapper, amount) {
    wrapper.vm.$store.commit('tradeBalance/setBalances', {
        [wrapper.vm.tokenName]: {available: amount.toString()},
    });
}


describe('TokenAirdropCampaign', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should check if airdrop campaign exists', () => {
        const data = {
            airdrop: null,
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        expect(wrapper.vm.hasAirdropCampaign).toBe(false);
        wrapper.vm.airdrop = {id: 2, actions: {}};
        expect(wrapper.vm.hasAirdropCampaign).toBe(true);
    });

    it('should check if disable save button', () => {
        const data = {
            actions: {
                twitterMessage: true,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        setBalance(wrapper, 0);
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.tokensAmount = null;
        wrapper.vm.participantsAmount = null;
        expect(wrapper.vm.btnDisabled).toBe(true);
        setBalance(wrapper, 800);
        wrapper.vm.tokensAmount = 0;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.tokensAmount = 50;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.participantsAmount = 99;
        expect(wrapper.vm.btnDisabled).toBe(true);
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.btnDisabled).toBe(false);
        setBalance(wrapper, 0);
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.vm.insufficientBalance).toBe(true);
        setBalance(wrapper, 0.01);
        wrapper.vm.tokensAmount = 0.01;
        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.vm.insufficientBalance).toBe(true);
        setBalance(wrapper, 0.0101);
        wrapper.vm.tokensAmount = 0.01;
        expect(wrapper.vm.btnDisabled).toBe(false);
        expect(wrapper.vm.insufficientBalance).toBe(false);
    });

    it('check amount of tokens is valid', () => {
        const data = {
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        expect(wrapper.vm.isAmountValid).toBe(true);
        wrapper.vm.tokensAmount = null;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.tokensAmount = '0.0009';
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.tokensAmount = 100;
        expect(wrapper.vm.isAmountValid).toBe(true);
    });

    it('check amount of participants is valid', () => {
        const data = {
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        expect(wrapper.vm.isParticipantsAmountValid).toBe(true);
        wrapper.vm.participantsAmount = null;
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

    describe('date validation should work properly', () => {
        it('if date is valid and showEndDate = true', () => {
            const data = {
                actions: {
                    twitterMessage: false,
                    twitterRetweet: false,
                    facebookMessage: false,
                    facebookPage: false,
                    facebookPost: false,
                    linkedinMessage: false,
                    youtubeSubscribe: false,
                    postLink: false,
                    visitExternalUrl: false,
                },
            };
            const wrapper = mockTokenAirdropCampaign(
                {},
                data
            );

            wrapper.vm.showEndDate = true;
            expect(wrapper.vm.isDateValid).toBe(true);
            expect(wrapper.vm.isDateEndValid).toBe(true);

            wrapper.vm.endDate = moment().add(1, 'day').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(true);
            expect(wrapper.vm.isDateEndValid).toBe(true);

            wrapper.vm.endDate = moment().add(1, 'month').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(true);
            expect(wrapper.vm.isDateEndValid).toBe(true);
        });

        it('if date is valid and showEndDate = false', () => {
            const data = {
                actions: {
                    twitterMessage: false,
                    twitterRetweet: false,
                    facebookMessage: false,
                    facebookPage: false,
                    facebookPost: false,
                    linkedinMessage: false,
                    youtubeSubscribe: false,
                    postLink: false,
                    visitExternalUrl: false,
                },
            };
            const wrapper = mockTokenAirdropCampaign(
                {},
                data
            );

            wrapper.vm.showEndDate = false;
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(true);

            wrapper.vm.endDate = moment().add(1, 'day').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(true);

            wrapper.vm.endDate = moment().add(1, 'month').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(true);
        });

        it('if date is invalid and showEndDate = true', () => {
            const data = {
                actions: {
                    twitterMessage: false,
                    twitterRetweet: false,
                    facebookMessage: false,
                    facebookPage: false,
                    facebookPost: false,
                    linkedinMessage: false,
                    youtubeSubscribe: false,
                    postLink: false,
                    visitExternalUrl: false,
                },
            };
            const wrapper = mockTokenAirdropCampaign(
                {},
                data
            );

            wrapper.vm.showEndDate = true;
            wrapper.vm.endDate = moment().subtract(1, 'day').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(false);

            wrapper.vm.endDate = moment().subtract(1, 'month').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(false);
        });

        it('if date is invalid and showEndDate = false', () => {
            const data = {
                actions: {
                    twitterMessage: false,
                    twitterRetweet: false,
                    facebookMessage: false,
                    facebookPage: false,
                    facebookPost: false,
                    linkedinMessage: false,
                    youtubeSubscribe: false,
                    postLink: false,
                    visitExternalUrl: false,
                },
            };
            const wrapper = mockTokenAirdropCampaign(
                {},
                data
            );

            wrapper.vm.showEndDate = false;
            wrapper.vm.endDate = moment().subtract(1, 'day').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(true);

            wrapper.vm.endDate = moment().subtract(1, 'month').format(wrapper.vm.options.format);
            expect(wrapper.vm.isDateValid).toBe(false);
            expect(wrapper.vm.isDateEndValid).toBe(true);
        });
    });

    it('check if airdrop campaign reward is valid', () => {
        const data = {
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        expect(wrapper.vm.isRewardValid).toBe(true);
        wrapper.vm.tokensAmount = null;
        wrapper.vm.participantsAmount = null;
        expect(wrapper.vm.isRewardValid).toBe(false);

        setBalance(wrapper, 150);
        wrapper.vm.tokensAmount = 100;
        wrapper.vm.participantsAmount = 100;
        expect(wrapper.vm.isRewardValid).toBe(true);

        wrapper.vm.tokensAmount = '0.01';
        expect(wrapper.vm.isRewardValid).toBe(true);

        wrapper.vm.participantsAmount = 200;
        expect(wrapper.vm.isRewardValid).toBe(false);
    });

    it('can load token balance', async () => {
        const data = {
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const props = {
            tokenName: 'test2',
        };
        const wrapper = mockTokenAirdropCampaign(
            props,
            data
        );

        wrapper.vm.$store.commit('tradeBalance/setBalances', null);

        expect(wrapper.vm.balanceLoaded).toBe(false);

        setBalance(wrapper, 1254.2356);

        expect(wrapper.vm.tokenBalance).toBe('1254.2356');
        expect(wrapper.vm.balanceLoaded).toBe(true);
    });

    it('can load token ongoing airdrop campaign', (done) => {
        const data = {
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const props = {
            tokenName: 'test2',
        };

        moxios.stubRequest('get_airdrop_campaign', {
            status: 200,
            response: {
                airdrop: {
                    id: 4,
                    actions: {},
                },
            },
        });

        const wrapper = mockTokenAirdropCampaign(
            props,
            data
        );


        expect(wrapper.vm.loading).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.hasAirdropCampaign).toBe(true);
            expect(wrapper.vm.airdropCampaignId).toBe(4);
            expect(wrapper.vm.loading).toBe(false);
            done();
        });
    });

    it('can create new token airdrop campaign', (done) => {
        const data = {
            airdrop: null,
            tokensAmount: '100',
            participantsAmount: 100,
            showEndDate: false,
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const props = {
            tokenName: 'test2',
        };
        const wrapper = mockTokenAirdropCampaign(
            props,
            data
        );

        setBalance(wrapper, 500);

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
        const data = {
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
                visitExternalUrl: false,
            },
        };
        const props = {
            tokenName: 'test5',
        };
        const wrapper = mockTokenAirdropCampaign(
            props,
            data
        );

        setBalance(wrapper, 50);

        expect(wrapper.vm.errorMessage).toBe('');
        wrapper.vm.createAirdropCampaign();

        expect(wrapper.vm.btnDisabled).toBe(true);
        expect(wrapper.vm.isRewardValid).toBe(false);
    });

    it('can delete ongoing airdrop campaign', (done) => {
        const data = {
            airdrop: {
                id: 3,
                actions: {},
            },
            actions: {
                twitterMessage: false,
                twitterRetweet: false,
                facebookMessage: false,
                facebookPage: false,
                facebookPost: false,
                linkedinMessage: false,
                youtubeSubscribe: false,
                postLink: false,
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

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
        const data = {
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
                visitExternalUrl: false,
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        wrapper.vm.setDefaultValues();
        wrapper.vm.showEndDate = true;

        expect(wrapper.vm.tokensAmount).toBe(100);
        expect(wrapper.vm.participantsAmount).toBe(100);
        expect(wrapper.vm.isDateValid).toBe(true);
    });

    it('should generate iframe code correctly', () => {
        const data = {
            airdrop: {
                id: 1,
                actions: {
                    foo: true,
                    bar: true,
                },
            },
        };
        const wrapper = mockTokenAirdropCampaign(
            {},
            data
        );

        expect(wrapper.vm.embedCode).toBe(
            '<iframe src="airdrop_embeded" width="500px" height="500px" style="border: none;" scrolling="no"></iframe>'
        );
    });

    it('Verify that the "saveFacebook" event is emitted correctly', async () => {
        const dataTest = 'facebook/jasm';

        const wrapper = mockTokenAirdropCampaign({currentFacebook: ''});

        wrapper.vm.saveFacebook(dataTest);

        expect(wrapper.vm.currentFacebook).toBe(dataTest);
        expect(wrapper.emitted('updated-facebook')).toBeTruthy();
        expect(wrapper.emitted('updated-facebook')[0]).toEqual([dataTest]);
    });

    it('Verify that the "saveYoutube" event is emitted correctly', async () => {
        const dataTest = 'jasm';

        const wrapper = mockTokenAirdropCampaign({currentYoutube: ''});

        wrapper.vm.saveYoutube(dataTest);

        expect(wrapper.vm.currentYoutube).toBe(dataTest);
        expect(wrapper.emitted('updated-youtube')).toBeTruthy();
        expect(wrapper.emitted('updated-youtube')[0]).toEqual([dataTest]);
    });

    describe('shouldTruncate', () => {
        it('should be false if tokenName.length <= 10', () => {
            const props = {
                tokenName: 'moonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            expect(wrapper.vm.shouldTruncate).toBe(false);
        });

        it('should be true if tokenName.length > 10', () => {
            const props = {
                tokenName: 'moonparkmoonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            expect(wrapper.vm.shouldTruncate).toBe(true);
        });
    });

    describe('tooltipConfig ', () => {
        it('should return config properly if tokenName.length <= 10', () => {
            const props = {
                tokenName: 'moonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            expect(wrapper.vm.tooltipConfig).toBe(null);
        });

        it('should return config properly if tokenName.length > 10', () => {
            const props = {
                tokenName: 'moonparkmoonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            const expectedConfig = {
                title: 'moonparkmoonpark',
                customClass: 'tooltip-custom',
                variant: 'light',
            };

            expect(wrapper.vm.tooltipConfig).toEqual(expectedConfig);
        });
    });

    describe('truncatedTokenName ', () => {
        it('should return truncated token name properly if tokenName.length < 10', () => {
            const props = {
                tokenName: 'moonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            expect(wrapper.vm.truncatedTokenName).toBe('moonpark');
        });

        it('should return truncated token name properly if tokenName.length > 10', () => {
            const props = {
                tokenName: 'moonparkmoonpark',
            };
            const wrapper = mockTokenAirdropCampaign(props);

            expect(wrapper.vm.truncatedTokenName).toBe('moonparkmo...');
        });
    });
});
