import {createLocalVue, mount} from '@vue/test-utils';
import TokenName from '../../js/components/token/TokenName';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('TokenName', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });
    
    // Commented due the component hard reloading. Consider to resolve TODO and uncomment it
    // it('can be edited if editable', (done) => {
    //     const localVue = mockVue();
    //     const wrapper = mount(TokenName, {
    //         localVue,
    //         propsData: {
    //             name: 'foo',
    //             csrfToken: 'csrfToken',
    //             updateUrl: 'updateUrl',
    //             editable: true,
    //         },
    //     });
    //     moxios.stubRequest('updateUrl', {
    //         status: 204,
    //         response: [],
    //     });
    //
    //     moxios.stubRequest('is_token_exchanged', {
    //         status: 200,
    //         response: false,
    //     });
    //
    //     moxios.wait(() => {
    //         expect(wrapper.find('input').exists()).to.deep.equal(false);
    //         expect(wrapper.vm.editingName).to.deep.equal(false);
    //
    //         wrapper.vm.editName();
    //
    //         let input = wrapper.find('input');
    //
    //         expect(input.exists()).to.deep.equal(true);
    //         expect(wrapper.vm.editingName).to.deep.equal(true);
    //
    //         input.setValue('bar');
    //         wrapper.vm.editName();
    //
    //         expect(wrapper.vm.currentName).to.deep.equal('bar');
    //         expect(wrapper.vm.newName).to.deep.equal('bar');
    //         done();
    //     });
    // });

    it('can not be edited if not editable', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                csrfToken: 'csrfToken',
                updateUrl: 'updateUrl',
                editable: false,
            },
        });

        moxios.stubRequest('is_token_exchanged', {
            status: 200,
            response: true,
        });

        moxios.wait(() => {
            expect(wrapper.find('input').exists()).to.deep.equal(false);
            expect(wrapper.vm.editingName).to.deep.equal(false);

            wrapper.vm.editName();

            expect(wrapper.find('input').exists()).to.deep.equal(false);
            expect(wrapper.vm.editingName).to.deep.equal(false);

            done();
        });
    });

    it('can not be edited if token exchanged', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                csrfToken: 'csrfToken',
                updateUrl: 'updateUrl',
                editable: true,
            },
        });

        moxios.stubRequest('is_token_exchanged', {
            status: 200,
            response: true,
        });

        moxios.wait(() => {
            expect(wrapper.find('input').exists()).to.deep.equal(false);
            expect(wrapper.vm.editingName).to.deep.equal(false);

            wrapper.vm.editName();

            expect(wrapper.find('input').exists()).to.deep.equal(false);
            expect(wrapper.vm.editingName).to.deep.equal(false);

            done();
        });
    });
});
