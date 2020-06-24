import {mount, shallowMount} from '@vue/test-utils';
import Avatar from '../../js/components/Avatar';

describe('Avatar', () => {
    it('should render default fallback image if empty', () => {
        const wrapper = shallowMount(Avatar, {
            propsData: {
                image: '',
                fallback: '/media/default_profile.png',
                type: 'token',
                size: 'small',
                symbol: 'BTC',
            },
        });

        expect(wrapper.find('img').attributes('src')).to.have.string('default_profile.png');
    });

    it('should add proper classes', () => {
        const wrapper = shallowMount(Avatar, {
            propsData: {
                image: '',
                type: 'token',
            },
        });

        wrapper.setProps({size: 'small'});
        expect(wrapper.find('div.avatar').classes('avatar__small')).to.be.true;

        wrapper.setProps({size: 'medium'});
        expect(wrapper.find('div.avatar').classes('avatar__medium')).to.be.true;

        wrapper.setProps({size: 'large'});
        expect(wrapper.find('div.avatar').classes('avatar__large')).to.be.true;
    });

    it('should not allow editing avatar if disabled', () => {
        const wrapper = mount(Avatar, {
            propsData: {
                image: '',
                editable: false,
                type: 'token',
            },
        });

        expect(wrapper.contains('input')).to.be.false;
    });

    it('should allow editing avatar if enabled', () => {
        const wrapper = mount(Avatar, {
            propsData: {
                image: '',
                editable: true,
                type: 'token',
            },
        });

        expect(wrapper.contains('input')).to.be.true;
    });
});
