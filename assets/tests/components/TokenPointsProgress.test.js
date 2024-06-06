import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenPointsProgress from '../../js/components/token/TokenPointsProgress';
import {tokenDeploymentStatus} from '../../js/utils/constants';
import Vuex from 'vuex';
import tokenStatistics from '../../js/storage/modules/token_statistics';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('TokenPointsProgress', () => {
    it('should calculate token points gained correctly with any item', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                tokenStatistics: {
                    ...tokenStatistics,
                    state: {
                        stats: {
                            releasePeriod: '-',
                        },
                    },
                },
            },
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: '',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly "release period"', async () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                tokenStatistics: {
                    ...tokenStatistics,
                    state: {
                        stats: {
                            releasePeriod: '-',
                        },
                    },
                },
            },
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: '',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
                hasReleasePeriod: true,
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(22);
        await wrapper.setProps({hasReleasePeriod: false});
        expect(wrapper.vm.tokenPointsGained).toBe(0);
        store.commit('tokenStatistics/setStats', {releasePeriod: 10});
        expect(wrapper.vm.tokenPointsGained).toBe(22);
        store.commit('tokenStatistics/setStats', {releasePeriod: '-'});
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly "token description"', async () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStatistics},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: '',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: 'Description',
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(22);
        await wrapper.setProps({tokenDescription: null});
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly "user profile with out trade anonymously"', async () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStatistics},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: 'Description',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(22);
        await wrapper.setProps({profileAnonymously: '1'});
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly if is not MINTME token', async () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                tokenStatistics: {
                    ...tokenStatistics,
                    state: {
                        stats: {
                            releasePeriod: '-',
                        },
                    },
                },
            },
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: false,
                profileAnonymously: '',
                profileDescription: 'Description',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.deployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(66);
        await wrapper.setProps({profileAnonymously: '1'});
        expect(wrapper.vm.tokenPointsGained).toBe(44);
    });
    it('should calculate token points gained correctly with all items', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {
                tokenStatistics: {
                    ...tokenStatistics,
                    state: {
                        stats: {
                            releasePeriod: '-',
                        },
                    },
                },
            },
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: 'description',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: 'description',
                tokenFacebook: 'facebook',
                tokenStatus: tokenDeploymentStatus.deployed,
                tokenWebsite: 'website',
                tokenYoutube: 'youtube',
            },
        });
        store.commit('tokenStatistics/setStats', {releasePeriod: 10});
        expect(wrapper.vm.tokenPointsGained).toBe(100);
    });

    describe('token status', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStatistics},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: '',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        it('should calculate token points gained correctly on token status "not deployed"', () => {
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
        it('should calculate token points gained correctly on token status "token pending"', async () => {
            await wrapper.setProps({
                tokenStatus: tokenDeploymentStatus.pending,
            });
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
        it('should calculate token points gained correctly "token deployed"', async () => {
            await wrapper.setProps({
                tokenStatus: tokenDeploymentStatus.deployed,
            });
            expect(wrapper.vm.tokenPointsGained).toBe(22);
        });
    });

    describe('social media status', () => {
        const localVue = mockVue();
        const store = new Vuex.Store({
            modules: {tokenStatistics},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
                isCreatedOnMintmeSite: true,
                profileAnonymously: '',
                profileDescription: '',
                profileLastname: 'last name',
                profileName: 'name',
                tokenDescription: null,
                tokenFacebook: null,
                tokenStatus: tokenDeploymentStatus.notDeployed,
                tokenWebsite: null,
                tokenYoutube: null,
            },
        });
        it('should calculate token points gained correctly any social media', () => {
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
        it('should calculate token points gained correctly all social media', async () => {
            await wrapper.setProps({tokenFacebook: 'facebook'});
            await wrapper.setProps({tokenYoutube: 'youtube'});
            await wrapper.setProps({tokenWebsite: 'website'});
            expect(wrapper.vm.tokenPointsGained).toBe(12);
        });
        it('should calculate token points gained correctly when facebok dont exist', async () => {
            await wrapper.setProps({tokenFacebook: null});
            await wrapper.setProps({tokenYoutube: 'youtube'});
            await wrapper.setProps({tokenWebsite: 'website'});
            expect(wrapper.vm.tokenPointsGained).toBe(12);
        });
        it('should calculate token points gained correctly when youtube dont exist', async () => {
            await wrapper.setProps({tokenFacebook: 'facebook'});
            await wrapper.setProps({tokenWebsite: 'website'});
            await wrapper.setProps({tokenYoutube: null});
            expect(wrapper.vm.tokenPointsGained).toBe(12);
        });
        it('should calculate token points gained correctly when website dont exist', async () => {
            await wrapper.setProps({tokenFacebook: 'facebook'});
            await wrapper.setProps({tokenYoutube: 'youtube'});
            await wrapper.setProps({tokenWebsite: null});
            expect(wrapper.vm.tokenPointsGained).toBe(12);
        });
    });
});
