import '../../js/main';
import {mount} from '@vue/test-utils';
import TokenName from '../../js/components/token/TokenName';
import moxios from 'moxios';

describe('TokenName', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('can be edited if editable', (done) => {
        const wrapper = mount(TokenName, {
            propsData: {
                name: 'Token name test',
                csrfToken: 'csrfToken',
                updateUrl: 'updateUrl',
                editable: true,
            },
        });

        moxios.stubRequest('updateUrl', {
            status: 204,
            response: [],
        });

        expect(wrapper.vm.$v).to.exist;
        expect(wrapper.vm.newName).to.equal('Token name test');
        expect(wrapper.vm.$v.newName.$error).to.be.false;
        expect(wrapper.find('input').exists()).to.deep.equal(false);
        expect(wrapper.vm.editingName).to.deep.equal(false);

        wrapper.vm.editName();

        let input = wrapper.find('input');

        expect(input.exists()).to.deep.equal(true);
        expect(wrapper.vm.editingName).to.deep.equal(true);

        input.setValue('Token name test 2');
        wrapper.vm.editName();
        wrapper.vm.$v.$touch();
        moxios.wait(() => {
            expect(wrapper.vm.currentName).to.deep.equal('Token name test');
            expect(wrapper.vm.newName).to.deep.equal('Token name test 2');
            done();
        });
    });

    it('can not be edited if not editable', () => {
        const wrapper = mount(TokenName, {
            propsData: {
                name: 'foo',
                csrfToken: 'csrfToken',
                updateUrl: 'updateUrl',
                editable: false,
            },
        });

        expect(wrapper.find('input').exists()).to.deep.equal(false);
        expect(wrapper.vm.editingName).to.deep.equal(false);

        wrapper.vm.editName();

        expect(wrapper.find('input').exists()).to.deep.equal(false);
        expect(wrapper.vm.editingName).to.deep.equal(false);
    });
});
