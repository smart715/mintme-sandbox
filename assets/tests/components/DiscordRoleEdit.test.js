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

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function mockDiscordRoleEdit(props = {}, data = {}) {
    return shallowMount(DiscordRoleEdit, {
        localVue: mockVue(),
        directives: {
            'b-tooltip': {},
        },
        propsData: {
            role: testRole,
            ...props,
        },
        data() {
            return {
                ...data,
            };
        },
    });
}

describe('Discord Role', () => {
    describe('test validations', () => {
        it('requiredBalance is required', () => {
            const wrapper = mockDiscordRoleEdit();

            wrapper.setProps({role: {...testRole, requiredBalance: '            '}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.not_empty');
        });

        it('requiredBalance should be decimal', () => {
            const wrapper = mockDiscordRoleEdit();

            wrapper.setProps({role: {...testRole, requiredBalance: 'foo'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.decimal');
        });

        it('between validation', () => {
            const wrapper = mockDiscordRoleEdit();

            wrapper.setProps({role: {...testRole, requiredBalance: '0'}});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.between');

            wrapper.setProps({requiredBalance: '1000001'});

            expect(wrapper.vm.$v.$invalid).toBe(true);

            expect(wrapper.vm.errorMessage).toBe('discord.rewards.special_roles.requiredBalance.between');
        });
    });

    it('updates valid on mount', () => {
        const wrapper = mockDiscordRoleEdit();

        expect(wrapper.emitted().update[0]).toEqual([testRole, 'valid', true]);
    });

    it('test update', () => {
        const wrapper = mockDiscordRoleEdit();

        wrapper.vm.update('name', 'foo');

        expect(wrapper.emitted().update[1]).toEqual([testRole, 'name', 'foo']);
    });

    it('updates valid when role prop changes', () => {
        const wrapper = mockDiscordRoleEdit();

        let role = {...testRole, valid: false};

        wrapper.setProps({role});

        expect(wrapper.emitted().update[1]).toEqual([role, 'valid', true]);
    });
});
