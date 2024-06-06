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
            Vue.prototype.$logger = {error: () => {}};
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

        moxios.stubRequest('get_discord_info', {
            status: 200,
            response: {
                config: {
                    enabled: true,
                    guildId: 1,
                },
                roles: [
                    testRole,
                ],
            },
        });

        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        expect(wrapper.vm.loaded).toBe(false);
        expect(wrapper.vm.enabled).toBe(false);
        expect(wrapper.vm.guildId).toBe(null);
        expect(wrapper.vm.currentRoles).toEqual([]);


        moxios.wait(() => {
            expect(wrapper.vm.loaded).toBe(true);
            expect(wrapper.vm.enabled).toBe(true);
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

    it('test updateRole', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
            },
        });

        const role = {...testRole};

        wrapper.vm.updateRole(role, 'name', 'test1');

        expect(role.name).toBe('test1');
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

    it('test save doesn\'t do anything is saveDisabled is true', async () => {
        const mock = jest.fn();
        const localVue = mockVue();
        DiscordRewardsEdit.methods.loadDiscordInfo = jest.fn();
        const wrapper = shallowMount(DiscordRewardsEdit, {
            localVue,
            propsData: {
                tokenName: 'foo',
                authUrl: 'testAuthUrl',
                saving: true,
            },
            mocks: {
                $axios: {
                    single: {
                        get: mock,
                    },
                },
            },
        });

        await wrapper.vm.save();

        expect(mock).not.toHaveBeenCalled();
    });
});
