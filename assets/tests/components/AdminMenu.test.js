import {shallowMount, createLocalVue} from '@vue/test-utils';
import {SidebarMenu} from 'vue-sidebar-menu';
import AdminMenu from '../../js/components/AdminMenu';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(SidebarMenu);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
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
        isUserLogged: false,
        ...props,
    };
};

const nonAuthorizedMenuTest = [
    {
        header: true,
        title: 'hacker_menu.permissions.title',
    },
    {
        child: [
            {
                href: 'quick-registration',
                title: 'hacker_menu.quick_registration',
            },
        ],
        icon: 'fa fa-sign-in-alt',
        title: 'Quick Menu',
    },
    {
        child: [
            {
                href: 'hacker-toggle-info-bar',
                title: 'hacker_menu.tester_widget',
            },
        ],
        icon: 'fa fa-tasks',
        title: 'Tester Options',
    },
];

const authorizedMenuTest = [
    {
        header: true,
        title: 'hacker_menu.title',
    },
    {
        child: [
            {
                href: 'hacker-set-role',
                title: 'hacker_menu.permissions.admin',
            },
            {
                href: 'hacker-set-role',
                title: 'hacker_menu.permissions.user',
            },
        ],
        icon: 'fa fa-anchor',
        title: 'hacker_menu.permissions.title',
    },
    {
        child: [],
        icon: 'fa fa-cubes',
        title: 'hacker_menu.crypto.title',
    },
    {
        child: [
            {
                href: 'hacker-toggle-info-bar',
                title: 'hacker_menu.tester_widget',
            },
        ],
        icon: 'fa fa-tasks',
        title: 'Tester Options',
    },
];

describe('AdminMenu', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(AdminMenu, {
            sync: false,
            localVue: localVue,
            propsData: createSharedTestProps(),
        });

        moxios.install();
        moxios.stubRequest('tokens', {
            status: 200,
            response: {},
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "clickedStyles" return the correct value', async () => {
        await wrapper.setData({
            isClicked: false,
        });

        expect(wrapper.vm.clickedStyles).toBe('v-sidebar-menu vsm-collapsed');

        await wrapper.setData({
            isClicked: true,
        });

        expect(wrapper.vm.clickedStyles).toBe('');
    });

    it('Verify that "menu" return the correct value', async () => {
        expect(wrapper.vm.menu).toEqual(nonAuthorizedMenuTest);

        await wrapper.setProps({
            isUserLogged: true,
        });

        expect(wrapper.vm.menu).toEqual(authorizedMenuTest);
    });

    it('Verify that "menuWidth" return the correct value', async () => {
        expect(wrapper.vm.menuWidth).toBe('30px');

        await wrapper.setData({
            isClicked: true,
        });

        expect(wrapper.vm.menuWidth).toBe('350px');
    });
});
