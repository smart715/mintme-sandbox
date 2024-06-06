import {shallowMount, createLocalVue} from '@vue/test-utils';
import PlainTextView from '../../js/components/UI/PlainTextView';
import VueSanitize from 'vue-sanitize';
import {sanitizeOptions} from '../../js/utils/constants.js';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(VueSanitize, sanitizeOptions);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('PlainTextView', () => {
    it('parse youtube', () => {
        const wrapper = shallowMount(PlainTextView, {
            localVue: mockVue(),
            propsData: {text: 'youtube.com/watch?v=ro_Vwk_LTHc'},
        });

        expect(wrapper.vm.parsedText).toBe(`<iframe
                    class='position-relative'
                    width='100%'
                    height='315'
                    src='https://www.youtube.com/embed/ro_Vwk_LTHc'
                    title='YouTube'
                    frameborder='0'
                    allow='accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture'
                    allowfullscreen
                ></iframe>`);
    });

    it('parse image', () => {
        const wrapper = shallowMount(PlainTextView, {
            localVue: mockVue(),
            propsData: {text: 'domain.com/testImage.png'},
        });

        expect(wrapper.vm.parsedText).toBe(
            '<a rel="noopener nofollow" target="_blank" ' +
            'href="http://domain.com/testImage.png">domain.com/testImage.png</a>' +
            '<br><img class=\"mw-100\" src=\"http://domain.com/testImage.png\" />'
        );
    });

    it('parse link', () => {
        const wrapper = shallowMount(PlainTextView, {
            localVue: mockVue(),
            propsData: {text: 'www.testurl.com'},
        });

        expect(wrapper.vm.parsedText).toBe(
            '<a rel="noopener nofollow" target="_blank" href="http://www.testurl.com">www.testurl.com</a>'
        );
    });
});
