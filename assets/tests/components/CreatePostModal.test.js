import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import CreatePostModal from '../../js/components/modal/CreatePostModal';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        subunit: 4,
        visible: true,
        tokenName: 'token-jasm',
        editPost: null,
        ...props,
    };
}

describe('CountedTextarea', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CreatePostModal, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    describe('Check that "post" returns the correct value', () => {
        it('When "editPost" is not null', async () => {
            const dataTest = {
                content: 'jasm',
                amount: '4',
                title: 'Titlejasm',
                shareReward: '4',
            };

            await wrapper.setProps({
                editPost: dataTest,
            });

            expect(wrapper.vm.post).toEqual(dataTest);
        });

        it('When "editPost" is null', async () => {
            await wrapper.setProps({
                editPost: null,
            });

            expect(wrapper.vm.post).toEqual({
                content: '',
                amount: '',
                title: '',
                shareReward: '',
            });
        });
    });

    describe('Check that "modalHeader" works correctly', () => {
        it('When the "id" exists', async () => {
            await wrapper.setProps({
                editPost: {
                    id: 1,
                },
            });

            expect(wrapper.vm.modalHeader).toBe('post.edit_modal_title');
        });

        it('When the "id" does not exist', async () => {
            await wrapper.setProps({
                editPost: null,
            });

            expect(wrapper.vm.modalHeader).toBe('post.create_modal_title');
        });
    });

    describe('Verify that the "close" event is emitted correctly', () => {
        it('when "submitting" is true', async () => {
            await wrapper.setData({
                submitting: true,
            });

            wrapper.vm.cancel();

            expect(wrapper.emitted('close')).toBeFalsy();
        });

        it('when "submitting" is false', async () => {
            await wrapper.setData({
                submitting: false,
            });

            wrapper.vm.cancel();

            expect(wrapper.emitted('close')).toBeTruthy();
        });
    });

    it('Check that "reset" works correctly', async () => {
        await wrapper.setData({
            title: 'jasm',
            content: 'jams-jasm',
            amount: '22',
            shareReward: '22',
        });

        wrapper.vm.reset();

        expect(wrapper.vm.title).toBe('');
        expect(wrapper.vm.content).toBe('');
        expect(wrapper.vm.amount).toBe('0');
        expect(wrapper.vm.shareReward).toBe('0');
    });
});
