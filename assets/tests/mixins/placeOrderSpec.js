import Vue from 'vue';
import placeOrderMixin from '../../js/mixins/placeOrder';

describe('placeOrderMixin', function() {
    const vm = new Vue({
        mixins: [placeOrderMixin],
    });

    it('triggers showModalAction correctly', function() {
        vm.showModalAction();
        expect(vm.modalSuccess).to.deep.equals(false);
        expect(vm.modalTitle).to.deep.equals('Order Failed');

        vm.showModalAction({result: 2});
        expect(vm.modalSuccess).to.deep.equals(false);
        expect(vm.modalTitle).to.deep.equals('Order Failed');

        vm.showModalAction({result: 1});
        expect(vm.modalSuccess).to.deep.equals(true);
        expect(vm.modalTitle).to.deep.equals('Order Created');

        vm.showModalAction({result: 2});
        expect(vm.modalSuccess).to.deep.equals(false);
        expect(vm.modalTitle).to.deep.equals('Order Failed');

        vm.showModalAction({result: 1, message: 'Done'});
        expect(vm.modalSuccess).to.deep.equals(true);
        expect(vm.modalTitle).to.deep.equals('Done');
    });
});
