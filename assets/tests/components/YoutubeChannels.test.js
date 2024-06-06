import {createLocalVue, shallowMount} from '@vue/test-utils';
import YoutubeChannels from '../../js/components/token/youtube/YoutubeChannels';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        urls: [
            'https://www.youtube.com/watch?v=ixlBozNAiSc',
            'https://www.youtube.com/watch?v=NJY9zvSpZAE',
            'https://www.youtube.com/watch?v=TloJfH1QxQg',
        ],
        ...props,
    };
}

describe('YoutubeChannels', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = shallowMount(YoutubeChannels, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "processChannelUrls" works correctly', async () => {
        await wrapper.setData({
            youtubeChannelsInfo: [],
            channelsIds: [],
        });

        wrapper.vm.processChannelUrls();

        expect(wrapper.vm.channelsIds).toHaveLength(3);
        expect(wrapper.vm.youtubeChannelsInfo).toHaveLength(3);
    });

    it('Verify that "fetchChannelsInfo" works correctly', async (done) => {
        moxios.stubRequest('youtube_channels_info', {
            status: 200,
            response: ['fetchedInfoTest'],
        });

        await wrapper.setData({
            channelsIds: JSON.stringify(['watch', 'watch', 'watch']),
        });

        await wrapper.vm.fetchChannelsInfo();

        moxios.wait(() => {
            expect(wrapper.vm.fetchedInfo).toEqual(['fetchedInfoTest']);
            done();
        });
    });

    describe('Verify that "processFetchedInfo" works correctly', () => {
        it('When the ID match', async () => {
            await wrapper.setData({
                fetchedInfo: {'id1': {}},
                youtubeChannelsInfo: [{
                    id: 'id1',
                    loaded: false,
                }],
            });

            wrapper.vm.processFetchedInfo();

            expect(wrapper.vm.youtubeChannelsInfo).toEqual([{'id': 'id1', 'loaded': true}]);
        });

        it('When the ID does not match', async () => {
            await wrapper.setData({
                fetchedInfo: {'id2': {}},
                youtubeChannelsInfo: [{
                    id: 'id1',
                    loaded: false,
                }],
            });

            wrapper.vm.processFetchedInfo();

            expect(wrapper.vm.youtubeChannelsInfo).toEqual([{'id': 'id1', 'loaded': false}]);
        });
    });
});
