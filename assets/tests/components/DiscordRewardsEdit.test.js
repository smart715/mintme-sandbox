import {shallowMount, createLocalVue} from '@vue/test-utils';
import DiscordRewardsEdit from '../../js/components/token/discord/DiscordRewardsEdit';
import Vuelidate from 'vuelidate';
import moxios from 'moxios';
import axios from 'axios';

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
            Vue.prototype.$toasted = {show: () => false};
        },
    });

    localVue.use(Vuelidate);
    return localVue;
}

const testRole = {
    id: 1,
    name: 'foo',
    color: '#000000',
    requiredBalance: '2',
    valid: true,
};

describe('Discord Rewards', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('loads initial data correctly', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        expect(wrapper.vm.loaded).toBe(false);
        expect(wrapper.vm.enabled).toBe(false);
        expect(wrapper.vm.specialRolesEnabled).toBe(false);
        expect(wrapper.vm.guildId).toBe(null);
        expect(wrapper.vm.currentRoles).toEqual([]);

        moxios.stubRequest('get_discord_info', {
            status: 200,
            response: {
                config: {
                    enabled: true,
                    specialRolesEnabled: true,
                    guildId: 1,
                },
                roles: [
                    testRole,
                ],
            },
        });

        moxios.wait(() => {
            expect(wrapper.vm.loaded).toBe(true);
            expect(wrapper.vm.enabled).toBe(true);
            expect(wrapper.vm.specialRolesEnabled).toBe(true);
            expect(wrapper.vm.guildId).toBe(1);
            expect(wrapper.vm.currentRoles).toEqual([testRole]);
            done();
        });
    });

    it('compute saveDisabled correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
            mocks: {
                $v: {
                    $invalid: false,
                },
            },
        });

        expect(wrapper.vm.saveDisabled).toBe(false);

        describe('when saving is true', () => {
            wrapper.setData({saving: true});

            expect(wrapper.vm.saveDisabled).toBe(true);

            wrapper.setData({saving: false});
        });

        describe('when $v.$invalid is true', () => {
            wrapper.vm.$v.$invalid = true;

            expect(wrapper.vm.saveDisabled).toBe(true);
        });
    });

    it('compute roles correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        wrapper.setData({currentRoles: [testRole], newRoles: [testRole]});

        expect(wrapper.vm.roles).toEqual([testRole, testRole]);
    });

    it('test remove role', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        wrapper.setData({currentRoles: [testRole], newRoles: [testRole]});

        wrapper.vm.removeRole(testRole);

        expect(wrapper.vm.newRoles.length).toBe(0);

        wrapper.vm.removeRole(testRole);

        expect(wrapper.vm.currentRoles.length).toBe(0);
        expect(wrapper.vm.removedRoles.length).toBe(1);
    });

    it('computes errorMessage correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        describe('when specialRolesEnabled is true but there are no roles', () => {
            wrapper.setData({specialRolesEnabled: true});

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.required');
        });

        describe('when required balances are not unique', () => {
            wrapper.setData({newRoles: [{...testRole, name: 'test1'}, {...testRole, name: 'test2'}]});

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.unique_balances');
        });
    });

    it('test updateRole', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        let role = {...testRole};

        wrapper.vm.updateRole(role, 'name', 'test1');

        expect(role.name).toBe('test1');
        expect(wrapper.vm.anyChange).toBe(true);
    });

    it('test removeGuild', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        wrapper.setData({enabled: true, guildId: 1});

        moxios.stubRequest('remove_guild', {status: 200});

        wrapper.vm.removeGuild();

        moxios.wait(() => {
            expect(wrapper.vm.enabled).toBe(false);
            expect(wrapper.vm.guildId).toBe(null);
            done();
        });
    });

    it('test save doesn\'t do anything is saveDisabled is true', () => {
        const mock = jest.fn();
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
            mocks: {
                $axios: {
                    single: {
                        get: mock,
                    },
                },
            },
            methods: {
                loadDiscordInfo: () => Promise.resolve(),
            },
        });

        wrapper.setData({saving: true});

        wrapper.vm.save();

        expect(mock).not.toHaveBeenCalled();
    });

    it('test save', (done) => {
        const mock = jest.fn();
        mock.mockReturnValue(Promise.resolve());
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
            methods: {
                loadDiscordInfo: mock,
            },
        });

        let newRoles = [{...testRole, name: 'new', requiredBalance: '1', valid: true}];
        let currentRoles = [{...testRole, name: 'current', valid: true}];
        let removedRoles = [{...testRole, name: 'removed'}];

        wrapper.setData({newRoles, currentRoles, removedRoles});

        wrapper.vm.save();

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();

            let data = JSON.parse(request.config.data);

            expect(data.specialRolesEnabled).toBe(false);
            expect(data.newRoles).toEqual(newRoles);
            expect(data.currentRoles).toEqual(currentRoles);
            expect(data.removedRoles).toEqual(removedRoles);

            request.respondWith({status: 200, response: {}})
                .then(() => {
                    expect(mock).toHaveBeenCalled();
                    done();
                });
        });
    });
});
