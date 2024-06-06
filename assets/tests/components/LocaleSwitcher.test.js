import {shallowMount, createLocalVue} from '@vue/test-utils';
import LocaleSwitcher from '../../js/components/LocaleSwitcher';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

const options = {
    localVue: mockVue(),
    propsData: {
        currentLocale: 'en',
        flags: '{"en":{"label":"English","flag":"gb"},"es":{"label":"Español","flag":"es"}}',
    },
};

const expectedFlagClassName = {
    en: 'gb',
    es: 'es',
};

describe('LocaleSwitcher', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(LocaleSwitcher, {
            ...options,
            propsData: {...options.propsData},
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should show flag with current locale', () => {
        expect(wrapper.html().includes('flag-icon-' + expectedFlagClassName.en)).toBe(true);
        expect(wrapper.html().includes('flag-icon-' + expectedFlagClassName.es)).toBe(true);
    });

    it('should toggle menu on click when mode is "click"', async () => {
        await wrapper.setProps({mode: 'click'});

        wrapper.findComponent('.dropdown-toggle').trigger('click');

        expect(wrapper.vm.showLangMenu).toBe(true);

        wrapper.findComponent('.dropdown-toggle').trigger('click');

        expect(wrapper.vm.showLangMenu).toBe(false);
    });

    it('should not toggle menu on click when mode is "hover" and screen size is greater than medium', async () => {
        await wrapper.setProps({mode: 'hover'});

        Object.defineProperty(window, 'innerWidth', {value: 992}); // ScreenMediaSize.MD;

        expect(wrapper.vm.showLangMenu).toBe(false);

        wrapper.findComponent('.dropdown-toggle').trigger('click');

        expect(wrapper.vm.showLangMenu).toBe(false);
    });

    it('should toggle menu on click when mode is "hover" and screen size is smaller than medium', async () => {
        await wrapper.setProps({mode: 'hover'});

        Object.defineProperty(window, 'innerWidth', {value: 991}); // ScreenMediaSize.SM;

        wrapper.findComponent('.dropdown-toggle').trigger('click');

        expect(wrapper.vm.showLangMenu).toBe(true);

        wrapper.findComponent('.dropdown-toggle').trigger('click');

        expect(wrapper.vm.showLangMenu).toBe(false);
    });

    it('should call the changeLocale method with the correct locale value when a dropdown item is clicked', () => {
        wrapper.vm.changeLocale = jest.fn();
        const changeLocaleSpy = jest.spyOn(wrapper.vm, 'changeLocale');

        const dropdownItem = wrapper.findComponent('.dropdown-item');
        dropdownItem.trigger('click');

        expect(changeLocaleSpy).toHaveBeenCalledWith('en');
    });

    it('should change the href to the expected value when the changeLocale method is called', async (done) => {
        const backupWindow = global.window;

        moxios.stubRequest('change_locale', {
            status: 200,
        });

        const locale = 'es';

        global.window = Object.create(window);
        Object.defineProperty(window, 'location', {
            value: {
                href: 'http://localhost',
            },
            writable: true,
        });
        window.location.origin = 'http://localhost';

        await wrapper.vm.changeLocale(locale);

        moxios.wait(() => {
            expect(window.location.href).toBe('http://localhost/es');
            delete global.window;
            global.window = backupWindow;

            done();
        });
    });
});
