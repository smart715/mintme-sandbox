import {shallowMount, createLocalVue} from '@vue/test-utils';
import DiscordRoleEdit from '../../js/components/token/discord/DiscordRoleEdit';
import Vuelidate from 'vuelidate';

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

describe('Discord Role', () => {
    describe('test validations', () => {
        it('name is required', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, name: '           '}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.name.not_empty');
        });

        it('maxNameLength validation', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, name: 'foooooooooooooooooooooooooooooooooooooooo'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.name.max_length');
        });

        it('requiredBalance is required', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, requiredBalance: '            '}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.not_empty');
        });

        it('requiredBalance should be decimal', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, requiredBalance: 'foo'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.decimal');
        });

        it('between validation', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, requiredBalance: '0'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.between');

            wrapper.setProps({requiredBalance: '1000001'});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.between');
        });

        it('color should be hexadecimal code', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(DiscordRoleEdit, {
                localVue,
                propsData: {
                    role: testRole,
                },
                data() {
                    return {
                        maxNameLength: 10,
                        minRequiredBalance: 1,
                        maxRequiredBalance: 10,
                    };
                },
            });

            wrapper.setProps({role: {...testRole, color: '          '}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.color.hex');

            wrapper.setProps({role: {...testRole, color: 'abcdef'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.color.hex');

            wrapper.setProps({role: {...testRole, color: '#abcdef'}});

            expect(wrapper.vm.$v.$invalid).toBe(false);

            expect(wrapper.vm.errorMessage).toBe('');
        });
    });

    it('updates valid on mount', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRoleEdit, {
            localVue,
            propsData: {
                role: testRole,
            },
        });

        expect(wrapper.emitted().update[0]).toEqual([testRole, 'valid', true]);
    });

    it('test update', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRoleEdit, {
            localVue,
            propsData: {
                role: testRole,
            },
        });

        wrapper.vm.update('name', 'foo');

        expect(wrapper.emitted().update[1]).toEqual([testRole, 'name', 'foo']);
    });

    it('updates valid when role prop changes', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(DiscordRoleEdit, {
            localVue,
            propsData: {
                role: testRole,
            },
        });

        let role = {...testRole, valid: false};

        wrapper.setProps({role});

        expect(wrapper.emitted().update[1]).toEqual([role, 'valid', true]);
    });
});
