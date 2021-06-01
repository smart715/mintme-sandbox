import {shallowMount, createLocalVue} from '@vue/test-utils';
import Avatar from '../../js/components/Avatar';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    return localVue;
}

describe('Avatar', () => {
    it('should render default fallback image if empty', () => {
        const wrapper = shallowMount(Avatar, {
            localVue: mockVue(),
            propsData: {
                image: '',
                fallback: '/media/default_profile.png',
                type: 'token',
                size: 'small',
                symbol: 'BTC',
            },
        });

        expect(wrapper.find('img').attributes('src')).toContain('default_profile.png');
    });

    it('should add proper classes', () => {
        const wrapper = shallowMount(Avatar, {
            localVue: mockVue(),
            propsData: {
                image: '',
                type: 'token',
            },
        });

        wrapper.setProps({size: 'small'});
        expect(wrapper.find('div.avatar').classes('avatar__small')).toBe(true);

        wrapper.setProps({size: 'medium'});
        expect(wrapper.find('div.avatar').classes('avatar__medium')).toBe(true);

        wrapper.setProps({size: 'large'});
        expect(wrapper.find('div.avatar').classes('avatar__large')).toBe(true);
    });

    it('should not allow editing avatar if disabled', () => {
        const wrapper = shallowMount(Avatar, {
            localVue: mockVue(),
            propsData: {
                image: '',
                editable: false,
                type: 'token',
            },
        });

        expect(wrapper.contains('imageuploader-stub')).toBe(false);
    });

    it('should allow editing avatar if enabled', () => {
        const wrapper = shallowMount(Avatar, {
            localVue: mockVue(),
            propsData: {
                image: '',
                editable: true,
                type: 'token',
            },
        });

        expect(wrapper.contains('imageuploader-stub')).toBe(true);
    });
});
