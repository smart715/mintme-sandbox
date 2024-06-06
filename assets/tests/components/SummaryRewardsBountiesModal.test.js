import {createLocalVue, shallowMount} from '@vue/test-utils';
import axios from 'axios';
import moxios from 'moxios';
import moment from 'moment';
import Vuex from 'vuex';
import SummaryRewardsBountiesModal from '../../js/components/bountiesAndRewards/modal/SummaryRewardsBountiesModal';
import {
    GENERAL,
    TYPE_BOUNTY,
    TYPE_REWARD,
    REWARD_NOT_COMPLETED,
    REWARD_COMPLETED,
    REWARD_REFUNDED,
    REWARD_DELIVERED,
} from '../../js/utils/constants';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
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
        visible: true,
        item: itemTest,
        tokenAvatar: 'TokenAvatarTest',
        tokenName: 'TokenNameTest',
        ...props,
    };
};

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations) {
    return new Vuex.Store({
        modules: {
            user: {
                namespaced: true,
                getters: {
                    getId: () => '2',
                },
            },
            rewardsAndBounties: {
                mutations,
                getters: {
                    getBountiesMaxLimit: () => 1,
                },
                namespaced: true,
            },
        },
    });
}

const date = new Date('2022, 07, 15');

const itemTest = {
    createdAt: moment(date).unix(),
    hasPendingParticipants: false,
    type: TYPE_BOUNTY,
    quantity: 1,
    title: 'titleTest',
    participants: [
        {
            note: 'test',
            reward: [],
            id: 1,
        },
        {
            isRequesting: false,
        },
    ],
};

const testRewards = [
    {
        frozenAmount: '99900000.000000000000',
        participants: [],
        price: '100000.000000000000',
        quantity: 999,
        quantityReached: true,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        type: TYPE_REWARD,
        volunteers: [],
    },
];

const memberTest = [
    {
        _showDetails: true,
        isRequesting: false,
        type: 'participant',
    },
    {
        _showDetails: true,
        id: 1,
        note: 'test',
        reward: [],
        type: 'participant',
    },
];


const volunteerTest = [
    {
        id: 1,
        note: 'test',
        reward: testRewards,
        isRequesting: false,
        status: REWARD_NOT_COMPLETED,
        type: 'participant',
        price: '100',
        user: {
            id: 1,
            profile: {
                nickname: 'nicknameTest',
            },
        },
    },
    {
        id: 2,
        note: 'test',
        reward: [],
        status: REWARD_COMPLETED,
        type: 'participant',
        price: '100',
        user: {
            id: 2,
            profile: {
                nickname: 'nicknameTest',
            },
        },
    },
];

describe('SummaryRewardsBountiesModal', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            editReward: jest.fn(),
        };

        store = createSharedTestStore(mutations);

        wrapper = shallowMount(SummaryRewardsBountiesModal, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createSharedTestProps(),
            attachTo: document.body,
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "title" return the correct value', () => {
        const title = wrapper.vm.item.title;
        expect(wrapper.vm.title).toBe(title);
    });

    it('Verify that "quantity" return the correct value', () => {
        expect(wrapper.vm.quantity).toBe('(2/1)');
    });

    it('Verify that "name" return the correct value', () => {
        const name = wrapper.props().tokenName;
        expect(wrapper.vm.name).toBe(name);
    });

    it('Verify that "hasMembers" return the correct value', () => {
        expect(wrapper.vm.hasMembers).toBeTruthy();
    });

    it('Verify that "isBounty" return the correct value', async () => {
        expect(wrapper.vm.isBounty).toBeTruthy();

        await wrapper.setProps({
            item: {
                type: TYPE_REWARD,
            },
        });

        expect(wrapper.vm.isBounty).toBeFalsy();
    });

    it('Verify that "translationContext" return the correct value', () => {
        const value = {
            title: wrapper.vm.item.title,
        };

        expect(wrapper.vm.translationContext).toEqual(value);
    });

    it('Verify that "deleteIconHint" return the correct value', async () => {
        expect(wrapper.vm.deleteIconHint).toBe('delete');

        await wrapper.setProps({
            item: {
                hasPendingParticipants: true,
                type: TYPE_REWARD,
            },
        });

        expect(wrapper.vm.deleteIconHint).toBe('reward_bounty.not_completed_reward');

        await wrapper.setProps({
            item: {
                hasPendingParticipants: true,
                type: TYPE_BOUNTY,
            },
        });

        expect(wrapper.vm.deleteIconHint).toBe('reward_bounty.not_completed_bounty');
    });

    it('Verify that "formattedDate" return the correct value', () => {
        const value = moment.unix(wrapper.vm.item.createdAt)
            .format(`${GENERAL.timeFormat} ${GENERAL.dateFormat}`);

        expect(wrapper.vm.formattedDate).toBe(value);
    });

    it('Verify that "isPendingMember" works correctly', () => {
        expect(wrapper.vm.isPendingMember(volunteerTest[0])).toBeTruthy();
        expect(wrapper.vm.isPendingMember(volunteerTest[1])).toBeFalsy();
    });

    it('Verify that "isCompletedMember" works correctly', () => {
        expect(wrapper.vm.isCompletedMember(volunteerTest[0])).toBeFalsy();
        expect(wrapper.vm.isCompletedMember(volunteerTest[1])).toBeTruthy();
    });

    it('Verify that "rejectMember" works correctly', () => {
        wrapper.vm.rejectMember(memberTest[1]);

        const value = {...memberTest[1], reward: wrapper.vm.item};

        expect(wrapper.emitted('reject-member')).toBeTruthy();
        expect(wrapper.emitted('reject-member')[0]).toEqual([value]);
    });

    it('Verify that "showMemberConfirm" works correctly', () => {
        wrapper.vm.showMemberConfirm(volunteerTest[1]);

        expect(wrapper.vm.memberConfirmationMessage).toBe('bounties_rewards.manage.member.add.confirm');
        expect(wrapper.vm.showMemberConfirmModal).toBeTruthy();
        expect(wrapper.vm.currentMember).toEqual(volunteerTest[1]);
    });

    it('Verify that "acceptVolunteer" works correctly', () => {
        const value = {
            slug: wrapper.vm.item.slug,
            memberId: wrapper.vm.currentMember.id,
        };

        wrapper.vm.acceptVolunteer();

        expect(wrapper.emitted('accept-member')).toBeTruthy();
        expect(wrapper.emitted('accept-member')[0]).toEqual([value]);
    });

    it('Verify that "closeModal" works correctly', () => {
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('Verify that "editItem" works correctly', () => {
        wrapper.vm.editItem();
        expect(wrapper.emitted('edit')).toBeTruthy();
    });

    it('Verify that "removeItem" works correctly', () => {
        wrapper.vm.removeItem();
        expect(wrapper.emitted('remove')).toBeTruthy();
    });

    it('Verify that "refund" works correctly', () => {
        const value = {
            ...memberTest[1], reward: wrapper.vm.item,
        };

        wrapper.vm.refund(memberTest[1]);

        expect(wrapper.emitted('refund-member')).toBeTruthy();
        expect(wrapper.emitted('refund-member')[0]).toEqual([value]);
    });

    it('Verify that "setDelivered" works correctly', () => {
        const value = {
            slug: wrapper.vm.item.slug,
            member: memberTest[0],
            status: REWARD_DELIVERED,
        };

        wrapper.vm.setDelivered(memberTest[0]);

        expect(wrapper.emitted('save-participant-status')).toBeTruthy();
        expect(wrapper.emitted('save-participant-status')[0]).toEqual([value]);
    });

    describe('Verify the different reward statuses', () => {
        it('REWARD_NOT_COMPLETED', () => {
            expect(wrapper.vm.getHumanizedStatus(REWARD_NOT_COMPLETED)).toBe('bounty.status.not_completed');
        });

        it('REWARD_REFUNDED', () => {
            expect(wrapper.vm.getHumanizedStatus(REWARD_REFUNDED)).toBe('bounty.status.refunded');
        });

        it('REWARD_COMPLETED', async () => {
            await wrapper.setProps({
                item: {
                    type: TYPE_REWARD,
                },
            });
            expect(wrapper.vm.getHumanizedStatus(REWARD_COMPLETED)).toBe('reward.status.in_delivery');

            await wrapper.setProps({
                item: {
                    type: TYPE_BOUNTY,
                },
            });
            expect(wrapper.vm.getHumanizedStatus(REWARD_COMPLETED)).toBe('bounty.status.completed');
        });

        it('REWARD_DELIVERED', () => {
            expect(wrapper.vm.getHumanizedStatus(REWARD_DELIVERED)).toBe('reward.status.delivered');
        });

        it('DEFAULT', () => {
            expect(wrapper.vm.getHumanizedStatus('')).toBe('bounty.status.completed');
        });
    });
});
