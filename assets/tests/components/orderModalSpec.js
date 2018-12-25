import Vue from 'vue';
import bModal from 'bootstrap-vue/es/components/modal/modal';
Vue.component('b-modal', bModal);
import OrderModal from '../../components/modal/OrderModal';
import {mount} from '../testHelper';

describe('OrderModal', () => {
    it('computed title property is correct', () => {
        let orderModal = mount(OrderModal, {
            propsData: {type: true, visible: true},
        });
        expect(orderModal.title).to.equal('Order Created');
        orderModal = mount(OrderModal, {
            propsData: {type: false, visible: true},
        });
        expect(orderModal.title).to.equal('Order Failed');
    });
});
