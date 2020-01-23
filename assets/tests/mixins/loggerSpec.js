import Vue from 'vue';
import LoggerMixin from '../../js/mixins/logger';
import axios from "../../js/axios";

jest.mock('axios');

describe('logger', function() {
    const client = axios.create();

    const vm = new Vue({
        mixins: [LoggerMixin],
        install(Vue, options) {
            Vue.prototype.$axios = {
                retry: client,
                single: axios,
            };
        },
    });

    const data = {
        data: 'test',
        number: 5,
        array: ['test', '123', '555'],
    };

    it('triggers logs correctly', function() {
         vm.sendLogs('info', 'Info message', data);
         vm.sendLogs('alert', 'Alert message', data);
         vm.sendLogs('warning', 'Warning message', data);
         vm.sendLogs('error', 'Error message', data);
    });
});
