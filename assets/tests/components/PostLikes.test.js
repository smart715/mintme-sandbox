import {shallowMount} from '@vue/test-utils';
import PostLikes from '../../js/components/posts/PostLikes.vue';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

describe('PostLikes', () => {
    let wrapper;
    const likes = 5;

    beforeEach(() => {
        wrapper = shallowMount(PostLikes, {
            propsData: {
                likes,
            },
            components: {
                FontAwesomeIcon,
            },
        });
    });

    it('should render the correct number of likes', () => {
        expect(wrapper.text()).toContain(likes);
    });

    it('should emit like event when component is clicked', () => {
        wrapper.trigger('click');

        expect(wrapper.emitted('like')).toBeTruthy();
    });

    it('should render with isLiked class when isLiked prop is true', async () => {
        await wrapper.setProps({
            isLiked: true,
        });

        expect(wrapper.classes('text-primary')).toBe(true);
    });

    it('should render without isLiked class when isLiked prop is false', () => {
        wrapper.setProps({
            isLiked: false,
        });

        expect(wrapper.classes('text-primary')).toBe(false);
    });
});
