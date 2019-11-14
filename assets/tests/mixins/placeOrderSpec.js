import Vue from 'vue';
import placeOrderMixin from '../../js/mixins/place_order';

describe('placeOrderMixin', function() {
    const vm = new Vue({
        mixins: [placeOrderMixin],
    });

    it('triggers notification correctly', function() {
        vm.showNotification();

        vm.showNotification({result: 2});

        vm.showNotification({result: 1});

        vm.showNotification({result: 2});

        vm.showNotification({result: 1, message: 'Done'});
    });
});
