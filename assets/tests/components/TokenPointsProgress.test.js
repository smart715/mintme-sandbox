import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenPointsProgress from '../../js/components/token/TokenPointsProgress';
import {tokenDeploymentStatus} from '../../js/utils/constants';
import Vuex from 'vuex';
import tokenStats from '../../js/storage/modules/token_statistics';

describe('TokenPointsProgress', () => {
    it('should calculate token points gained correctly with any item', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 0,
                };
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly "release period"', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 4,
                };
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(4);
        wrapper.vm.tokenReleasePeriodPoint = 0;
        expect(wrapper.vm.tokenPointsGained).toBe(0);
    });
    it('should calculate token points gained correctly "token description"', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 0,
                };
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(4);
        wrapper.vm.tokenDescription = null;
        expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
    it('should calculate token points gained correctly "user profile with out trade anonymously"', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 0,
                };
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(4);
        wrapper.vm.profileAnonymously = '1';
        expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
    it('should calculate token points gained correctly with all items', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 4,
                };
            },
        });
        expect(wrapper.vm.tokenPointsGained).toBe(18);
    });
    describe('token status', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 0,
                };
            },
        });
        it('should calculate token points gained correctly on token status "not deployed"', () => {
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
        it('should calculate token points gained correctly on token status "token pending"', () => {
            wrapper.vm.tokenStatus = tokenDeploymentStatus.pending;
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
         it('should calculate token points gained correctly "token deployed"', () => {
            wrapper.vm.tokenStatus = tokenDeploymentStatus.deployed;
            expect(wrapper.vm.tokenPointsGained).toBe(4);
        });
    });
    describe('social media status', () => {
        const localVue = createLocalVue();
        localVue.use(Vuex);
        const store = new Vuex.Store({
            modules: {tokenStats},
        });
        const wrapper = shallowMount(TokenPointsProgress, {
            store,
            localVue,
            propsData: {
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
            data() {
                return {
                    tokenReleasePeriodPoint: 0,
                };
            },
        });
        it('should calculate token points gained correctly any social media', () => {
            expect(wrapper.vm.tokenPointsGained).toBe(0);
        });
        it('should calculate token points gained correctly all social media', () => {
            wrapper.vm.tokenFacebook = 'facebook';
            wrapper.vm.tokenYoutube = 'youtube';
            wrapper.vm.tokenWebsite = 'website';
            expect(wrapper.vm.tokenPointsGained).toBe(2);
        });
        it('should calculate token points gained correctly when facebok dont exist', () =>{
            wrapper.vm.tokenFacebook = null;
            wrapper.vm.tokenYoutube = 'youtube';
            wrapper.vm.tokenWebsite = 'website';
            expect(wrapper.vm.tokenPointsGained).toBe(2);
        });
        it('should calculate token points gained correctly when youtube dont exist', () =>{
            wrapper.vm.tokenFacebook = 'facebook';
            wrapper.vm.tokenWebsite = 'website';
            wrapper.vm.tokenYoutube = null;
            expect(wrapper.vm.tokenPointsGained).toBe(2);
        });
        it('should calculate token points gained correctly when website dont exist', () =>{
            wrapper.vm.tokenFacebook = 'facebook';
            wrapper.vm.tokenYoutube = 'youtube';
            wrapper.vm.tokenWebsite = null;
            expect(wrapper.vm.tokenPointsGained).toBe(2);
        });
    });
});
