import {shallowMount, createLocalVue} from '@vue/test-utils';
import Avatar from '../../js/components/Avatar';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    return localVue;
}

/**
 * @param {Object} propsData
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function createWrapper(propsData = {}, data = {}) {
    return shallowMount(Avatar, {
        localVue: mockVue(),
        propsData,
        data: () => data,
    });
}

describe('Avatar', () => {
    it('should render default fallback image if empty', () => {
        const wrapper = createWrapper({
            image: '',
            fallback: '/media/default_profile.png',
            type: 'token',
            size: 'small',
            symbol: 'BTC',
        });

        expect(wrapper.findComponent('img').attributes('src')).toContain('default_profile.png');
    });

    it('should add proper classes', async () => {
        const wrapper = createWrapper({
            image: '',
            type: 'token',
        });

        await wrapper.setProps({size: 'small'});
        expect(wrapper.findComponent('div.avatar').classes('avatar__small')).toBe(true);

        await wrapper.setProps({size: 'medium'});
        expect(wrapper.findComponent('div.avatar').classes('avatar__medium')).toBe(true);

        await wrapper.setProps({size: 'large'});
        expect(wrapper.findComponent('div.avatar').classes('avatar__large')).toBe(true);
    });

    it('should not allow editing avatar if disabled', () => {
        const wrapper = createWrapper({
            image: '',
            editable: false,
            type: 'token',
        });

        expect(wrapper.findComponent('imageuploader-stub').exists()).toBe(false);
    });

    it('should allow editing avatar if enabled', () => {
        const wrapper = createWrapper({
            image: '',
            editable: true,
            type: 'token',
        });

        expect(wrapper.findComponent('imageuploader-stub').exists()).toBe(true);
    });
});
