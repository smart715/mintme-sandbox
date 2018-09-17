import Vue from 'vue';
import bModal from 'bootstrap-vue/es/components/modal/modal';
Vue.component('b-modal', bModal);
import OrderModal from '../components/modal/OrderModal';

/** helper function that mounts and returns the Component Instances
 * @param {object} component to test.
 * @param {object} options properties to be pass by default
 * @return {object} The Component Instances.
 */
function mount(component, options) {
    const Constructor = Vue.extend(component);
    return new Constructor(options).$mount();
}

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
