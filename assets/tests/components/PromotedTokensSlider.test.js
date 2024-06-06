import {createLocalVue, shallowMount} from '@vue/test-utils';
import PromotedTokensSlider from '../../js/components/trading/PromotedTokensSlider';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} options
 * @return {Wrapper<Vue>}
 */
function createWrapper(options = {}) {
    const localVue = mockVue();
    PromotedTokensSlider.methods.setTranslateX = jest.fn();

    return shallowMount(PromotedTokensSlider, {
        localVue,
        propsData: {
            promotions: [],
        },
        ...options,
    });
}

const testTokens = [
    {
        name: 'token1',
        price: 1,
        image: {
            avatar_large: 'testAvatarLarge',
        },
    },
    {
        name: 'token2',
        price: 2,
        image: {
            avatar_large: 'testAvatarLarge',
        },
    },
];

describe('PromotedTokensSlider', () => {
    describe('showLeftArrow', () => {
        it('should calculate showLeftArrow correctly', () => {
            const wrapper = createWrapper();

            wrapper.setData({currentSlideIndex: 0});
            expect(wrapper.vm.showLeftArrow).toEqual(false);

            wrapper.setData({currentSlideIndex: 1});
            expect(wrapper.vm.showLeftArrow).toEqual(true);
        });
    });

    describe('showRightArrow', () => {
        it('should calculate showRightArrow correctly', () => {
            const wrapper = createWrapper();

            wrapper.setData({currentSlideIndex: 1, compensate: 0, compensated: true});
            expect(wrapper.vm.showRightArrow).toEqual(true);

            wrapper.setData({currentSlideIndex: 0, compensate: 1, compensated: false});
            expect(wrapper.vm.showRightArrow).toEqual(true);

            wrapper.setData({currentSlideIndex: 0, compensate: 0, compensated: true});
            expect(wrapper.vm.showRightArrow).toEqual(false);
        });
    });

    describe('initSlider', () => {
        it('should call initSlider a second time when slides-list is not initially rendered', () => {
            jest.useFakeTimers();

            const wrapper = createWrapper();
            const spy = jest.spyOn(wrapper.vm, 'initSlider');

            document.body.innerHTML = `
                <div class="slides-list-wrp">
                    <!-- No slides-list element initially -->
                </div>
            `;

            jest.advanceTimersByTime(2000);

            expect(spy).toHaveBeenCalledTimes(2);

            jest.clearAllTimers();
        });

        it('should initialize slider properties correctly', () => {
            const wrapper = createWrapper();
            const setTranslateX = jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation();
            jest.spyOn(wrapper.vm, 'autoplay').mockImplementation();
            const div = document.createElement('div');
            const originalQuerySelector = document.querySelector;

            wrapper.setData({
                randomizedTokens: testTokens,
            });

            document.querySelector = jest.fn((selector) => {
                if ('.slides-list' === selector) {
                    return {
                        offsetWidth: 500,
                    };
                }

                if ('.slides-list-wrp' === selector) {
                    return {
                        offsetWidth: 100,
                    };
                }

                // Fallback to the original implementation for unhandled cases
                return originalQuerySelector.call(document, selector);
            });

            div.setAttribute('class', 'slides-list-wrp');
            div.innerHTML = '<div class="slides-list"></div>';
            document.body.appendChild(div);

            wrapper.vm.initSlider();

            expect(setTranslateX).toHaveBeenCalled();
            expect(wrapper.vm.maxSlideIndex).toEqual(1);
            expect(wrapper.vm.compensate).toEqual(20);
        });
    });

    describe('autoplay', () => {
        it('should set moveSlideInstance correctly', () => {
            const wrapper = createWrapper();
            jest.spyOn(wrapper.vm, 'moveRight').mockImplementation();
            jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation();

            wrapper.vm.autoplay();

            expect(wrapper.vm.moveSlideInstance).toEqual(6);
        });
    });

    describe('moveRight', () => {
        it('should call setTranslateX and increment currentSlideIndex', () => {
            const wrapper = createWrapper();
            const setTranslateX = jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation();

            wrapper.setData({
                currentSlideIndex: 0,
                maxSlideIndex: 1,
            });

            wrapper.vm.moveRight();

            expect(setTranslateX).toHaveBeenCalled();
            expect(wrapper.vm.currentSlideIndex).toEqual(1);
        });

        it('should call setTranslateX and set compensated to true', () => {
            const wrapper = createWrapper();
            const setTranslateX = jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation();

            wrapper.setData({
                currentSlideIndex: 1,
                maxSlideIndex: 1,
                compensate: 1,
                compensated: false,
            });

            wrapper.vm.moveRight();

            expect(setTranslateX).toHaveBeenCalled();
            expect(wrapper.vm.compensated).toBe(true);
        });
    });

    describe('moveLeft', () => {
        it('should call setTranslateX and set compensated to false', () => {
            const wrapper = createWrapper();
            const setTranslateX = jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation();

            wrapper.setData({
                currentSlideIndex: 1,
                maxSlideIndex: 1,
                compensate: 1,
                compensated: true,
            });

            wrapper.vm.moveLeft();

            expect(setTranslateX).toHaveBeenCalled();
            expect(wrapper.vm.compensated).toBe(false);
        });

        it('should call setTranslateX and decrement currentSlideIndex', () => {
            const setTranslateX = jest.fn();
            const wrapper = createWrapper();
            jest.spyOn(wrapper.vm, 'setTranslateX').mockImplementation(setTranslateX);

            wrapper.setData({
                currentSlideIndex: 2,
                maxSlideIndex: 3,
                compensate: 1,
                compensated: false,
            });

            wrapper.vm.moveLeft();

            expect(setTranslateX).toHaveBeenCalled();
            expect(wrapper.vm.currentSlideIndex).toEqual(1);
        });
    });

    describe('setTranslateX', () => {
        it('shuffles the array correctly', () => {
            const wrapper = createWrapper();
            jest.spyOn(wrapper.vm, 'initSlider').mockImplementation();

            const array = [1, 2, 3, 4, 5];
            const shuffledArray = wrapper.vm.shuffleArray([...array]);

            expect(shuffledArray).not.toEqual(array);
        });
    });

    describe('getTokenUrl', () => {
        it('should return correct token url', () => {
            const wrapper = createWrapper();
            jest.spyOn(wrapper.vm, 'initSlider').mockImplementation();

            expect(wrapper.vm.getTokenUrl(testTokens[0])).toEqual('token_show_intro');
        });
    });
});
