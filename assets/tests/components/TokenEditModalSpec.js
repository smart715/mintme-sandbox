import Vue from 'vue';
import {mount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TokenEditModal from '../../js/components/modal/TokenEditModal';
Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenEditModal', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foo',
                newName: 'foo',
            },
        });
        const textInput = wrapper.find('input');

        expect(wrapper.vm.visible).to.equal(true);
        expect(wrapper.vm.currentName).to.equal('foo');
        expect(wrapper.vm.newName).to.equal('foo');
        expect(textInput.exists()).to.deep.equal(true);
    });

    it('throw required error when value is not set', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foo',
                newName: 'foo',
            },
        });
        const textInput = wrapper.find('input');

        textInput.setValue('');
        wrapper.vm.editName();
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.$error).to.deep.equal(true);
    });

    it('can not be deleted if exchanged', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {isTokenExchanged: true},
        });

        const btn = wrapper.findAll('.btn-cancel');
        const unavailable = btn.at(1).text().indexOf('Token deletion') !== -1;
        expect(unavailable).to.equal(wrapper.vm.isTokenExchanged);
    });

    it('can be deleted if not exchanged', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {isTokenExchanged: false},
        });

        const btn = wrapper.findAll('.btn-cancel');
        const unavailable = btn.at(1).text().indexOf('Token deletion') !== -1;
        expect(unavailable).to.equal(wrapper.vm.isTokenExchanged);
    });
});
