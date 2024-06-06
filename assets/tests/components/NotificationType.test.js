import {shallowMount, createLocalVue} from '@vue/test-utils';
import NotificationType from '../../js/components/NotificationType';
import axios from 'axios';
import {BTC, notificationTypes, TOKEN_DEFAULT_ICON_URL} from '../../js/utils/constants';
import {generateCoinAvatarHtml} from '../../js/utils';

const localVue = mockVue();

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
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        notification: {},
        ...props,
    };
};

const notificationTest = {
    type: '',
    date: '2000-02-22',
    number: null,
    jsonData: `{
        "nickname": "nickNameTest",
        "tokenName": "TokenTest",
        "profile": "ProfileTest",
        "kbLink": "test-link",
        "rewardTitle": "rewardTitleTest",
        "rewardToken": "rewardTokenTest",
        "cryptoSymbol": "BTC"
    }`,
};

const transactionTypes = [
    notificationTypes.deposit,
    notificationTypes.withdrawal,
    notificationTypes.transaction_delayed,
];

const rewardTypes = [
    notificationTypes.reward_participant,
    notificationTypes.reward_new,
    notificationTypes.reward_new_grouped,
    notificationTypes.bounty_new,
    notificationTypes.bounty_new_grouped,
    notificationTypes.reward_volunteer_new,
    notificationTypes.reward_volunteer_accepted,
    notificationTypes.reward_volunteer_rejected,
    notificationTypes.reward_participant_rejected,
    notificationTypes.reward_participant_delivered,
];

describe('NotificationType', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(NotificationType, {
            localVue: localVue,
            propsData: createSharedTestProps(),
            data() {
                return {
                    notificationTypes,
                };
            },
        });
    });

    afterEach(() => {
        notificationTest.type = '';
    });

    it('Check that "translationsContext" returns the correct value when "notification.type" is "deployed"', () => {
        notificationTest.type = notificationTypes.deployed;
        const tokenAvatar = generateCoinAvatarHtml({image: TOKEN_DEFAULT_ICON_URL, isUserToken: true});
        const received = {
            tokenAvatar,
            'tokenName': 'TokenTest',
            'urlToken': 'token_show_intro',
        };

        expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
    });

    it('Check that "translationsContext" returns the correct value when "notification.type" is "newPost"', () => {
        notificationTest.type = notificationTypes.newPost;

        const tokenAvatar = generateCoinAvatarHtml({image: TOKEN_DEFAULT_ICON_URL, isUserToken: true});
        const received = {
            'date': '2000-02-22',
            'number': 1,
            tokenAvatar,
            'tokenName': 'TokenTest',
            'urlToken': 'token_show_post',
        };

        expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
    });

    it('Check that "translationsContext" returns the correct value when "notification.type" is "newInvestor"', () => {
        notificationTest.type = notificationTypes.newInvestor;

        const received = {
            'profile': 'ProfileTest',
            'urlProfile': 'profile-view',
            'urlTrade': 'token_show_trade',
        };

        expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
    });

    it(
        'Check that "translationsContext" returns the correct value when "notification.type" is "tokenMarketingTips"',
        () => {
            notificationTest.type = notificationTypes.tokenMarketingTips;

            const received = {
                'title': 'test link',
                'url': 'kb_show',
            };

            expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
        }
    );

    it('Check that "translationsContext" returns the correct value when "notification.type" is "transaction"', () => {
        const received = {
            'urlWallet': 'wallet',
        };

        transactionTypes.forEach((type) => {
            notificationTest.type = type;
            expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
        });
    });

    it('Check that "translationsContext" returns the correct value when "notification.type" is "RewardType"', () => {
        const tokenAvatar = generateCoinAvatarHtml({image: TOKEN_DEFAULT_ICON_URL, isUserToken: true});
        const received = {
            'number': 1,
            'ownerNickname': undefined,
            'ownerProfileUrl': null,
            'pendingRewardApplication': 'token_settings',
            'rewardTitleFull': '',
            'rewardTitle': 'rewardTitleTest',
            'rewardTokenFull': '',
            'rewardToken': 'rewardTokenTest',
            tokenAvatar,
            'urlRewardFinalize': 'token_show_intro',
            'urlRewardSummary': 'token_settings',
            'urlRewardToken': 'token_show_intro',
        };

        rewardTypes.forEach((type) => {
            notificationTest.type = type;
            expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
        });
    });

    it(
        'Check that "translationsContext" returns the correct value when "notification.type" is "market_created"',
        () => {
            notificationTest.type = notificationTypes.market_created;

            const cryptoAvatar = generateCoinAvatarHtml({symbol: BTC.symbol, isCrypto: true});
            const tokenAvatar = generateCoinAvatarHtml({image: TOKEN_DEFAULT_ICON_URL, isUserToken: true});
            const received = {
                'cryptoSymbol': 'BTC',
                'marketUrl': 'token_show_trade',
                tokenAvatar,
                cryptoAvatar,
                'tokenName': 'TokenTest',
            };

            expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
        }
    );

    it(
        'Check that "translationsContext" returns the correct value when "notification.type" is "new_buy_order"',
        () => {
            notificationTest.type = notificationTypes.new_buy_order;

            const received = {
                'nickname': 'nickNameTest',
                'url': 'token_show_trade',

            };

            expect(wrapper.vm.translationsContext(notificationTest)).toEqual(received);
        }
    );
});
