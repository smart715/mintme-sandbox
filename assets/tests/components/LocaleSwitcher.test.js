import {shallowMount, createLocalVue} from '@vue/test-utils';
import LocaleSwitcher from '../../js/components/LocaleSwitcher';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

let options = {
    localVue: mockVue(),
    propsData: {
        currentLocale: 'en',
        flags: '{"en":{"label":"English","flag":"gb"},"es":{"label":"Español","flag":"es"}}',
    },
};

let expectedFlagClassName = {
    en: 'gb',
    es: 'es',
};

describe('LocaleSwitcher', () => {
   it('should show flag with current locale', () => {
       const wrapper = shallowMount(LocaleSwitcher, options);
       expect(wrapper.html().includes('flag-icon-'+expectedFlagClassName.en)).toBe(true);
       expect(wrapper.html().includes('flag-icon-'+expectedFlagClassName.es)).toBe(true);
   });
});
