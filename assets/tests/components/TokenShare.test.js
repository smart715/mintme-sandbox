import {shallowMount} from '@vue/test-utils';
import TokenShare from '../../js/components/token/TokenShare';

const $routing = {generate: (val, params) => val};

const objectForTestCorrectlyMounting = {
    stubs: ['social-sharing', 'network', 'font-awesome-icon', 'b-dropdown'],
    mocks: {
        $routing,
        $t: (val) => val,
    },
    propsData: {
        tokenName: 'testTokenName',
        tokenUrl: 'localhost://testTokenName',
    },
    directives: {
        'b-tooltip': {},
    },
};

const emptyUrls = {
    stubs: ['social-sharing', 'network', 'font-awesome-icon', 'b-dropdown'],
    mocks: {
        $routing,
        $t: (val) => val,
    },
    propsData: {
        tokenName: '',
        tokenUrl: '',
    },
    directives: {
        'b-tooltip': {},
    },
};

describe('TokenShare', () => {
    it('should compute description correctly', () => {
        const wrapper = shallowMount(TokenShare, objectForTestCorrectlyMounting);
        wrapper.vm.twitterDescription = 'foo';
        expect(wrapper.vm.description).toBe('foolocalhost://testTokenName');
    });

    it('should compute classSocialMediaMenu correctly', () => {
        const wrapper = shallowMount(TokenShare, objectForTestCorrectlyMounting);
        wrapper.vm.showSocialMediaMenu = true;
        expect(wrapper.vm.classSocialMediaMenu).toStrictEqual({'show': true});
    });

    it('Verify that toggleSocialMediaMenu works correctly', () => {
        const wrapper = shallowMount(TokenShare, objectForTestCorrectlyMounting);

        wrapper.vm.showSocialMediaMenu = true;
        wrapper.vm.toggleSocialMediaMenu();
        expect(wrapper.vm.showSocialMediaMenu).toBe(false);

        wrapper.vm.showSocialMediaMenu = false;
        wrapper.vm.toggleSocialMediaMenu();
        expect(wrapper.vm.showSocialMediaMenu).toBe(true);
    });

    it('Verify that hideSocialMediaMenu works correctly', () => {
        const wrapper = shallowMount(TokenShare, objectForTestCorrectlyMounting);

        wrapper.vm.showSocialMediaMenu = true;
        wrapper.vm.hideSocialMediaMenu();
        expect(wrapper.vm.showSocialMediaMenu).toBe(false);

        wrapper.vm.showSocialMediaMenu = false;
        wrapper.vm.hideSocialMediaMenu();
        expect(wrapper.vm.showSocialMediaMenu).toBe(false);
    });

    it('doesnt show unsetted urls', () => {
        const wrapper = shallowMount(TokenShare, emptyUrls);
        expect(wrapper.findAll('#token-social-media-icons > a').length).toBe(0);
    });
});
