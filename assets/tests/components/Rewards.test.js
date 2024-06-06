import {shallowMount, createLocalVue} from '@vue/test-utils';
import Rewards from '../../js/components/bountiesAndRewards/Rewards';

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

const testRewards = [
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
            image: {avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png'},
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'reward',
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
            image: {avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png'},
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'reward',
        volunteers: [],
        activeParticipantsAmount: 1,
    },
];

describe('Rewards', () => {
    it('shows no rewards if props has empty array', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Rewards, {
            localVue,
            propsData: {
                loaded: true,
                rewards: [],
                isOwner: true,
            },
            directives: {
                'b-tooltip': {},
            },
        });

        expect(wrapper.vm.isNotEmpty).toBe(false);
        expect(wrapper.html()).toContain('token.reward.no_rewards');
    });

    it('shows rewards if reward arr is not empty array', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Rewards, {
            localVue,
            propsData: {
                visible: true,
                rewards: testRewards,
            },
            directives: {
                'b-tooltip': {},
            },
        });

        expect(wrapper.vm.isNotEmpty).toBe(true);
    });

    it('should not show add new reward if user is not owner', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Rewards, {
            localVue,
            propsData: {
                isOwner: false,
                rewards: testRewards,
            },
            directives: {
                'b-tooltip': {},
            },
        });

        expect(wrapper.html()).not.toContain('plus-square');
    });

    it('should show add new button if user is owner and isSettingPage is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Rewards, {
            localVue,
            propsData: {
                isOwner: true,
                isSettingPage: true,
                rewards: testRewards,
            },
            directives: {
                'b-tooltip': {},
            },
        });
        expect(wrapper.html()).toContain('plus-square');
    });

    it('should emit open add modal event on click on add new button if isSettingPage is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Rewards, {
            localVue,
            propsData: {
                isOwner: true,
                isSettingPage: true,
                rewards: testRewards,
            },
            directives: {
                'b-tooltip': {},
            },
        });

        expect(wrapper.emitted('open-add-modal')).not.toBeTruthy();
        wrapper.findComponent('.card div h5 + div').trigger('click');
        expect(wrapper.emitted('open-add-modal')).toBeTruthy();
        expect(wrapper.emitted('open-add-modal')[0][0]).toBe('reward');
    });
});
