import Vue from 'vue';
import LoggerMixin from '../../js/mixins/logger';
jest.mock('axios');

describe('logger', function() {
    const vm = new Vue({
        mixins: [LoggerMixin],
    });

    const data = {
        data: 'test',
        number: 5,
        array: ['test', '123', '555'],
    };

    it('triggers logs correctly', async function() {
        await vm.sendLogs('info', 'Info message', data);
        await vm.sendLogs('alert', 'Alert message', data);
        await vm.sendLogs('warning', 'Warning message', data);
        await vm.sendLogs('error', 'Error message', data);
    });
});
