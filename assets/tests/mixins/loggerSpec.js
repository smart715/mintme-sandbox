import Vue from 'vue';
import LoggerMixin from '../../js/mixins/logger';
import moxios from 'moxios';
import axiosPlugin from '../../js/axios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();

    localVue.use(axiosPlugin);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

describe('logger', function() {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const data = {
        data: 'test',
        number: 5,
        array: ['test', '123', '555'],
    };

    it('triggers logs correctly', (done) => {
        const localVue = mockVue();
        const wrapper = mount(LoggerMixin, {
            localVue,
            mixins: [LoggerMixin],
        });

        moxios.stubRequest(this.$routing.generate('log'), {
            status: 200,
            level: 'info',
            message: 'Test log',
            context: JSON.stringify(data),
        });

        moxios.wait(() => {
            wrapper.vm.sendLogs('info', 'Test log', data);
            done();
        });
    });
});
