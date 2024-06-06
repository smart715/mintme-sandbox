import {shallowMount, createLocalVue} from '@vue/test-utils';
import Bounties from '../../js/components/bountiesAndRewards/Bounties';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
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
        tokenName: 'jasmToken',
        tokenAvatar: 'jasmTokenAvatar',
        isOwner: false,
        bounties: [],
        loaded: false,
        isMobileScreen: false,
        hideActions: false,
        isSettingPage: false,
        actionsLoaded: false,
        ...props,
    };
}

const testBounties = [
    {
        createdAt: 1622482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.000000000000',
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {
                avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png',
            },
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'bounty',
        volunteers: [],
        activeParticipantsAmount: 1,
    },
    {
        createdAt: 1122482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.0000',
        quantity: 999,
        slug: 'test',
        title: 'test',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {
                avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png',
            },
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'bounty',
        volunteers: [],
        activeParticipantsAmount: 1,
    },
];

describe('Bounties', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Bounties, {
            localVue: localVue,
            propsData: createSharedTestProps(),
            directives: {
                'b-tooltip': {},
            },
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('shows no bounties if props has empty array', async () => {
        await wrapper.setProps({
            loaded: true,
            bounties: [],
            isOwner: true,
        });

        expect(wrapper.vm.isNotEmpty).toBe(false);
        expect(wrapper.html()).toContain('token.bounties.no_rewards');
    });

    it('shows bounties if reward bounty arr is not empty array', async () => {
        await wrapper.setProps({
            visible: true,
            bounties: testBounties,
        });

        expect(wrapper.vm.isNotEmpty).toBe(true);
    });

    it('should not show add new button if user is not owner', async () => {
        await wrapper.setProps({
            isOwner: false,
            bounties: testBounties,
        });

        expect(wrapper.html()).not.toContain('plus-square');
    });

    it('should show add new button if user is owner and isSettingPage is true', async () => {
        await wrapper.setProps({
            isOwner: true,
            isSettingPage: true,
            bounties: [],
        });

        expect(wrapper.html()).toContain('plus-square');
    });

    it('should emit open add modal event on click on add new button if isSettingPage is true', async () => {
        await wrapper.setProps({
            isOwner: true,
            isSettingPage: true,
            bounties: [],
        });

        expect(wrapper.emitted('open-add-modal')).toBeFalsy();

        await wrapper.findComponent('.card div h5 + div').trigger('click');

        expect(wrapper.emitted('open-add-modal')).toBeTruthy();
        expect(wrapper.emitted('open-add-modal')[0][0]).toBe('bounty');
    });

    it('should return false on hasVolunteers if no volunteers', async () => {
        await wrapper.setProps({
            isOwner: true,
            bounties: [],
        });

        expect(wrapper.vm.hasVolunteers).toBe(false);
    });

    it('should return true on hasVolunteers if volunteers', async () => {
        await wrapper.setProps({
            isOwner: true,
            bounties: [{
                volunteers: [{}],
            }],
            isSettingPage: true,
        });

        expect(wrapper.vm.hasVolunteers).toBe(true);
    });

    it('should return correct volunteer data on computed volunteer', async () => {
        const bounty = {'name': 'test', 'volunteers': [{}]};

        await wrapper.setProps({
            isOwner: true,
            bounties: [bounty],
            isSettingPage: true,
        });

        expect(wrapper.vm.volunteers).toEqual([{'reward': bounty}]);
    });

    describe('Verify that "toggleDropdown" works correctly', () => {
        it('When "isToggleDropdown" is false', async () => {
            await wrapper.setData({
                isToggleDropdown: false,
            });

            expect(wrapper.vm.toggleDropdown()).toBe(true);
        });

        it('When "isToggleDropdown" is true', async () => {
            await wrapper.setData({
                isToggleDropdown: true,
            });

            expect(wrapper.vm.toggleDropdown()).toBe(false);
        });
    });

    describe('Verify that "showMoreButtonMessage" works correctly', () => {
        it('When "isListOpened" is false', async () => {
            await wrapper.setData({
                isListOpened: false,
            });

            expect(wrapper.vm.showMoreButtonMessage).toBe('token.row_tables.show_more');
        });

        it('When "isListOpened" is true', async () => {
            await wrapper.setData({
                isListOpened: true,
            });

            expect(wrapper.vm.showMoreButtonMessage).toBe('token.row_tables.show_less');
        });
    });

    describe('Verify that "translationsContext" returns the correct values', () => {
        it('When "tokenName" is different from empty', () => {
            expect(wrapper.vm.translationsContext).toEqual({tokenName: wrapper.vm.tokenName});
        });

        it('When "tokenName" is empty', async () => {
            await wrapper.setProps({
                tokenName: '',
            });

            expect(wrapper.vm.translationsContext).toEqual({tokenName: wrapper.vm.tokenName});
        });
    });
});
