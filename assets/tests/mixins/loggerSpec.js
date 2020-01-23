import Vue from 'vue';
import LoggerMixin from '../../js/mixins/logger';

describe('logger', function() {
    const vm = new Vue({
        mixins: [LoggerMixin],
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
