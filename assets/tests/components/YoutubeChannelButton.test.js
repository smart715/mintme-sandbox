import {createLocalVue, shallowMount} from '@vue/test-utils';
import YoutubeChannelButton from '../../js/components/token/youtube/YoutubeChannelButton';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        channelInfo: {
            loaded: true,
            img: 'jasmdnrc.png',
            description: 'jasmdnrc',
        },
        ...props,
    };
}

describe('YoutubeChannelButton', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(YoutubeChannelButton, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    describe('Verify that "channelImg" works correctly', () => {
        it('When "loaded" is true', () => {
            expect(wrapper.vm.channelImg).toBe('jasmdnrc.png');
        });

        it('When "loaded" is false', async () => {
            await wrapper.setProps({
                channelInfo: {
                    loaded: false,
                    img: 'jasmdnrc.png',
                    description: 'jasmdnrc',
                },
            });

            expect(wrapper.vm.channelImg).toEqual({'default': ''});
        });
    });

    describe('Verify that "description" works correctly', () => {
        it('When "loaded" is true', () => {
            expect(wrapper.vm.description).toBe('jasmdnrc');
        });

        it('When "loaded" is false', async () => {
            await wrapper.setProps({
                channelInfo: {
                    loaded: false,
                    img: 'jasmdnrc.png',
                    description: 'jasmdnrc',
                },
            });

            expect(wrapper.vm.description).toBe(false);
        });

        it('When the length of the description is greater than 80', async () => {
            const description = 'jasmdnrc'.repeat(80);
            const result = description.substring(0, 77) + '...';

            await wrapper.setProps({
                channelInfo: {
                    loaded: true,
                    img: 'jasmdnrc.png',
                    description: description,
                },
            });

            expect(wrapper.vm.description).toBe(result);
        });
    });
});
