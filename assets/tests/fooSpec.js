import Vue from 'vue';
import Foo from '../components/Foo.vue';

describe('Foo', () => {
    it('has a created hook', () => {
        expect(Foo.created).to.be.an('function');
    });

    it('correctly sets the message when created', () => {
        const vm = new Vue(Foo).$mount();
        expect(vm.message).to.deep.equal('bye!');
    });
});
