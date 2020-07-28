import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSearcher from '../../js/components/token/TokenSearcher';
import Axios from '../../js/axios';
import moxios from 'moxios';

describe('TokenSearcher', () => {
    beforeEach(() => {
       moxios.install();
       window.onbeforeunload = () => 'Stop full page reload!';
    });
    afterEach(() => {
        moxios.uninstall();
    });

    it('triggers searchUpdate()', (done) => {
        const localVue = createLocalVue();
        localVue.use(Axios);
        const wrapper = shallowMount(TokenSearcher, {
            localVue,
            propsData: {
                searchUrl: 'searchUrl',
            },
        });
        wrapper.vm.$axios.retry = wrapper.vm.$axios.single;
        moxios.stubRequest(/searchUrl\?.*/, {
            status: 200,
            response: [
                {name: 'TOKEN1'},
                {name: 'TOKEN2'},
            ],
        });
        wrapper.vm.searchUpdate('TOKEN');
        moxios.wait(() => {
            expect(wrapper.vm.items).toEqual(['TOKEN1', 'TOKEN2']);
            done();
        });
    });
    describe('input value', () => {
        const $routing = {generate: () => 'TokenUrl'};
        const wrapper = shallowMount(TokenSearcher, {
            mocks: {
                $routing,
            },
            propsData: {
                searchUrl: 'searchUrl',
            },
        });
        it('triggers onInputChange()', () => {
            wrapper.vm.onInputChange('TOKEN1');
            expect(wrapper.vm.input).toBe('TOKEN1');
        });
        it('triggers onItemClicked()', () => {
            wrapper.vm.onItemSelected('TOKEN2');
            expect(wrapper.vm.input).toBe('TOKEN2');
        });
    });
});
