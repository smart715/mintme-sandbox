import {shallowMount, createLocalVue} from '@vue/test-utils';
import ContentEditableTextarea from '../../js/components/UI/ContentEditableTextarea';
import moxios from 'moxios';
import axios from 'axios';
import Vue from 'vue';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function mockContentEditableTextarea() {
    const Component = Vue.component('foo', {
        mixins: [ContentEditableTextarea],
        template: '<div></div>',
    });
    const wrapper = shallowMount(Component, {
        localVue: mockVue(),
    });

    return wrapper;
}

describe('ContentEditableTextarea', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockContentEditableTextarea();
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('getHashtags', () => {
        it('should call cancelHashtagsRequest when getHashtags method invoked', () => {
            const cancelHashtagsRequest = jest.spyOn(wrapper.vm, 'cancelHashtagsRequest');

            wrapper.vm.getHashtags();

            expect(cancelHashtagsRequest).toHaveBeenCalled();
        });

        it('should set foundHashtags to response.data when when request succeed', (done) => {
            moxios.stubRequest('search_hashtags', {
                status: 200,
                response: ['test1', 'test2'],
            });

            wrapper.vm.getHashtags();

            moxios.wait(() => {
                expect(wrapper.vm.foundHashtags).toEqual(['test1', 'test2']);
                done();
            });
        });

        it('should set foundHashtags to empty array when request fails', async (done) => {
            await wrapper.setData({foundHashtags: ['test1', 'test2']});

            moxios.stubRequest('search_hashtags', {
                status: 403,
                response: {
                    message: 'error-message',
                },
            });

            wrapper.vm.getHashtags();

            moxios.wait(() => {
                expect(wrapper.vm.foundHashtags).toEqual([]);
                done();
            });
        });
    });

    describe('proceedHashtags', () => {
        it('should return text with replaced hashtags', () => {
            const text = 'text #test1';

            expect(wrapper.vm.proceedHashtags(text))
                .toEqual('text </span><span class=\"text-primary\">#test1</span><span>');
        });

        it('should return text with replaced hashtags and escaped tags', () => {
            const text = 'text <script> #test1';

            expect(wrapper.vm.proceedHashtags(text))
                .toEqual('text &lt;script&gt; </span><span class=\"text-primary\">#test1</span><span>');
        });

        it('should return text with replaced hashtags and do non escaped tags', () => {
            const text = 'text <script> #test1';

            expect(wrapper.vm.proceedHashtags(text, false))
                .toEqual('text <script> </span><span class=\"text-primary\">#test1</span><span>');
        });
    });

    describe('proceedText', () => {
        it('should set el.innerHTML to <div><br></div> when el.childNodes is empty', () => {
            const el = document.createElement('div');
            const div = document.createElement('div');
            const br = document.createElement('br');

            div.appendChild(br);
            el.appendChild(div);

            wrapper.vm.proceedText(el);

            expect(el.innerHTML).toEqual('<div><br></div>');
        });

        it('should set el.innerHTML to <div>text</div> when el.childNodes is empty', () => {
            const el = document.createElement('div');
            const div = document.createElement('div');
            const text = document.createTextNode('text');

            div.appendChild(text);
            el.appendChild(div);

            wrapper.vm.proceedText(el);

            expect(el.innerHTML).toEqual('<div>text</div>');
        });
    });

    describe('getTextFromHtml', () => {
        it('should return text from html', () => {
            const el = document.createElement('div');
            const div = document.createElement('div');
            const text = document.createTextNode('text');

            div.appendChild(text);
            el.appendChild(div);

            expect(wrapper.vm.getTextFromHtml(el)).toEqual('text');
        });

        it('should return text from html with new line', () => {
            const el = document.createElement('div');
            const div1 = document.createElement('div');
            const div2 = document.createElement('div');
            const text1 = document.createTextNode('text1');
            const text2 = document.createTextNode('text2');

            div1.appendChild(text1);
            div2.appendChild(text2);
            el.appendChild(div1);
            el.appendChild(div2);

            expect(wrapper.vm.getTextFromHtml(el)).toEqual('text1\ntext2');
        });

        it('should return text from html with replaced tags', () => {
            const el = document.createElement('div');
            const div = document.createElement('div');
            const text = document.createTextNode('text');

            div.appendChild(text);
            el.appendChild(div);

            expect(wrapper.vm.getTextFromHtml(el)).toEqual('text');
        });
    });

    describe('getHtmlFromText', () => {
        it('should return html from text', () => {
            const text = 'text';

            expect(wrapper.vm.getHtmlFromText(text)).toEqual('<div>text</div>');
        });

        it('should return html from text with new line', () => {
            const text = 'text1\ntext2';

            expect(wrapper.vm.getHtmlFromText(text)).toEqual('<div>text1</div><div>text2</div>');
        });
    });

    describe('onEditableBlockInput', () => {
        it('should call increase offset.pos', () => {
            window.getSelection().addRange = jest.fn();
            wrapper.vm.getCursorPosition = jest.fn( () => {
                return {pos: 0};
            });
            wrapper.vm.proceedText = jest.fn();
            wrapper.vm.getTextFromHtml = jest.fn();
            wrapper.vm.onInput = jest.fn();
            wrapper.vm.setCursorPosition = jest.fn( () => {
                return {collapse() {}};
            });
            const proceedText = jest.spyOn(wrapper.vm, 'proceedText');
            const handleHashtagRecommendations = jest.spyOn(wrapper.vm, 'handleHashtagRecommendations');

            wrapper.vm.onEditableBlockInput();

            expect(proceedText).toHaveBeenCalled();
            expect(handleHashtagRecommendations).toHaveBeenCalled();
        });

        it('should call setStart', () => {
            window.getSelection().addRange = jest.fn();
            wrapper.vm.getCursorPosition = jest.fn( () => {
                return {pos: 0};
            });
            wrapper.vm.proceedText = jest.fn();
            wrapper.vm.getTextFromHtml = jest.fn();
            wrapper.vm.onInput = jest.fn();
            const mockSetCursorPosition = jest.fn(() => ({
                collapse() {},
                startContainer: document,
                setStart() {},
            }));
            wrapper.vm.setCursorPosition = mockSetCursorPosition;
            const proceedText = jest.spyOn(wrapper.vm, 'proceedText');
            const handleHashtagRecommendations = jest.spyOn(wrapper.vm, 'handleHashtagRecommendations');
            wrapper.vm.$refs = {
                editable: document.createElement('div'),
            };

            wrapper.vm.onEditableBlockInput();

            expect(proceedText).toHaveBeenCalled();
            expect(handleHashtagRecommendations).toHaveBeenCalled();
            expect(mockSetCursorPosition).toHaveBeenCalled();
        });
    });

    describe('getCursorPosition', () => {
        it('should return stat', () => {
            const parent = document.createElement('div');
            const node = document.createElement('div');
            const offset = 0;
            const stat = {pos: 0, done: false};

            expect(wrapper.vm.getCursorPosition(parent, node, offset, stat)).toEqual({pos: 1, done: false});
        });

        it('should call getCursorPosition twice', () => {
            const parent = document.createElement('div');
            const node = document.createElement('div');
            const secondNode = document.createElement('span');
            const offset = 0;
            const stat = {pos: 0, done: false};
            const getCursorPositionSpy = jest.spyOn(wrapper.vm, 'getCursorPosition');

            parent.appendChild(node);
            wrapper.vm.getCursorPosition(parent, secondNode, offset, stat);

            expect(getCursorPositionSpy).toHaveBeenCalledTimes(2);
        });
    });

    describe('setCursorPosition', () => {
        it('should return range', () => {
            const parent = document.createElement('div');
            const range = document.createRange();
            const stat = {pos: 0, done: false};
            const newTagInserted = false;

            expect(wrapper.vm.setCursorPosition(parent, range, stat, newTagInserted)).toEqual(range);
        });

        it('should call setCursorPosition twice', () => {
            const parent = document.createElement('div');
            const child = document.createElement('div');
            const range = document.createRange();
            const stat = {pos: 0, done: false};
            const newTagInserted = false;
            const setCursorPositionSpy = jest.spyOn(wrapper.vm, 'setCursorPosition');

            parent.appendChild(child);
            wrapper.vm.setCursorPosition(parent, range, stat, newTagInserted);

            expect(setCursorPositionSpy).toHaveBeenCalledTimes(2);
        });
    });

    describe('handleContentEditableValueWatch', () => {
        it('should call handleContentEditableValueWatch', () => {
            const editableRef = document.createElement('div');
            wrapper.vm.editable = true;
            wrapper.vm.localValue = 'test';
            wrapper.vm.getTextFromHtml = jest.fn();
            wrapper.vm.getHtmlFromText = jest.fn();
            wrapper.vm.$refs['editable'] = editableRef;
            wrapper.vm.handleContentEditableValueWatch();

            expect(wrapper.vm.getTextFromHtml).toHaveBeenCalled();
            expect(wrapper.vm.getHtmlFromText).toHaveBeenCalled();
        });
    });

    describe('handleHashtagRecommendations', () => {
        it('should call debouncedGetHashtags', () => {
            const debouncedGetHashtags = jest.spyOn(wrapper.vm, 'debouncedGetHashtags');
            wrapper.vm.isHashtagNode = jest.fn(() => true);
            const parentFocusedNode = document.createElement('div');
            const focusedNode = document.createElement('div');
            parentFocusedNode.appendChild(focusedNode);
            const mockSelection = {
                focusNode: focusedNode,
                getRangeAt: jest.fn(() => ({
                    getBoundingClientRect: jest.fn(() => ({top: y, left: x})),
                })),
            };
            window.getSelection = jest.fn(() => mockSelection);
            document.getElementById = jest.fn(() => focusedNode);

            wrapper.vm.handleHashtagRecommendations();

            expect(debouncedGetHashtags).toHaveBeenCalled();
        });

        it('should call cancelHashtagsRequest', () => {
            const cancelHashtagsRequest = jest.spyOn(wrapper.vm, 'cancelHashtagsRequest');

            wrapper.vm.handleHashtagRecommendations();

            expect(cancelHashtagsRequest).toHaveBeenCalled();
        });
    });

    describe('chooseHashtag', () => {
        it('should call onEditableBlockInput, preventDefault and stopPropagation', async () => {
            wrapper.vm.onEditableBlockInput = jest.fn();
            wrapper.vm.isHashtagNode = jest.fn(() => true);
            await wrapper.setData({lastFocusedEl: document.createElement('div')});
            const event = {
                preventDefault: jest.fn(),
                stopPropagation: jest.fn(),
            };
            const hashtag = 'test';

            wrapper.vm.chooseHashtag(event, hashtag);

            expect(wrapper.vm.onEditableBlockInput).toHaveBeenCalled();
            expect(event.preventDefault).toHaveBeenCalled();
            expect(event.stopPropagation).toHaveBeenCalled();
        });

        it('should not call onEditableBlockInput', async () => {
            wrapper.vm.onEditableBlockInput = jest.fn();
            wrapper.vm.isHashtagNode = jest.fn(() => false);
            await wrapper.setData({lastFocusedEl: document.createElement('div')});
            const event = {
                preventDefault: jest.fn(),
                stopPropagation: jest.fn(),
            };
            const hashtag = 'test';

            wrapper.vm.chooseHashtag(event, hashtag);

            expect(wrapper.vm.onEditableBlockInput).not.toHaveBeenCalled();
        });
    });

    describe('cancelHashtagsRequest', () => {
        it('should call cancelHashtagsRequest', () => {
            wrapper.vm.hashtagsAxiosCancelTokenSource = {cancel: jest.fn()};
            wrapper.vm.cancelHashtagsRequest();

            expect(wrapper.vm.hashtagsAxiosCancelTokenSource.cancel).toHaveBeenCalled();
        });
    });

    describe('onEditableBlockClick', () => {
        it('should call cancelHashtagsRequest', () => {
            wrapper.vm.cancelHashtagsRequest = jest.fn();
            window.getSelection = jest.fn(() => ({focusNode: document.createElement('div')}));
            wrapper.vm.lastFocusedEl = document.createElement('div');

            wrapper.vm.onEditableBlockClick();

            expect(wrapper.vm.cancelHashtagsRequest).toHaveBeenCalled();
        });

        it('should not call cancelHashtagsRequest', async () => {
            wrapper.vm.cancelHashtagsRequest = jest.fn();
            const element = document.createElement('div');
            window.getSelection = jest.fn(() => ({focusNode: element}));
            await wrapper.setData({lastFocusedEl: element});

            wrapper.vm.onEditableBlockClick();

            expect(wrapper.vm.cancelHashtagsRequest).not.toHaveBeenCalled();
        });
    });

    describe('onEditableBlockBlur', () => {
        it('should call cancelHashtagsRequest', () => {
            wrapper.vm.cancelHashtagsRequest = jest.fn();
            wrapper.vm.foundHashtags = ['test'];

            wrapper.vm.onEditableBlockBlur();

            expect(wrapper.vm.cancelHashtagsRequest).toHaveBeenCalled();
            expect(wrapper.vm.foundHashtags).toEqual([]);
        });
    });

    describe('onEditableKeyDown', () => {
        it('should call chooseHashtag', () => {
            wrapper.vm.chooseHashtag = jest.fn();
            wrapper.vm.foundHashtags = [{value: 'test'}];
            const event = {
                keyCode: 9,
                preventDefault: jest.fn(),
            };

            wrapper.vm.onEditableKeyDown(event);

            expect(wrapper.vm.chooseHashtag).toHaveBeenCalled();
            expect(event.preventDefault).toHaveBeenCalled();
        });

        it('should not call chooseHashtag', () => {
            wrapper.vm.chooseHashtag = jest.fn();
            wrapper.vm.foundHashtags = [];
            const event = {
                keyCode: 9,
                preventDefault: jest.fn(),
            };

            wrapper.vm.onEditableKeyDown(event);

            expect(wrapper.vm.chooseHashtag).not.toHaveBeenCalled();
            expect(event.preventDefault).not.toHaveBeenCalled();
        });
    });

    describe('fixHintsContainerBoundaries', () => {
        it('should adjust height and left offset so it doesn\'t overflow window', () => {
            const hintsEl = document.createElement('div');
            hintsEl.style.left = '100px';
            hintsEl.style.top = '100px';
            hintsEl.getBoundingClientRect = jest.fn(() => ({top: 1200, left: 1200}));
            document.getElementById = jest.fn(() => hintsEl);
            window.innerHeight = 1000;
            window.innerWidth = 1000;

            wrapper.vm.fixHintsContainerBoundaries();

            expect(hintsEl.style.height).toEqual('-204px');
            expect(hintsEl.style.left).toEqual('0px');
        });

        it('should set height to auto and should not adjust left offset', () => {
            const hintsEl = document.createElement('div');
            hintsEl.style.left = '100px';
            hintsEl.style.top = '100px';
            hintsEl.getBoundingClientRect = jest.fn(() => ({top: 800}));
            document.getElementById = jest.fn(() => hintsEl);
            window.innerHeight = 1000;
            window.innerWidth = 1000;

            wrapper.vm.fixHintsContainerBoundaries();

            expect(hintsEl.style.height).toEqual('auto');
            expect(hintsEl.style.left).toEqual('100px');
        });
    });

    describe('isHashtagNode', () => {
        it('should return true when text-primary class is found', () => {
            const node = document.createElement('div');
            const child = document.createElement('div');
            node.classList.add('text-primary');
            node.appendChild(child);

            expect(wrapper.vm.isHashtagNode(child)).toEqual(true);
        });

        it('should return false when text-primary class is not found', () => {
            const node = document.createElement('div');
            const child = document.createElement('div');
            node.appendChild(child);

            expect(wrapper.vm.isHashtagNode(child)).toEqual(false);
        });
    });

    describe('getHashtagsRecommendationHintId', () => {
        it('should return hashtags_recommendation_hint_test + textareaId', () => {
            wrapper.vm.textareaId = 'test';
            expect(wrapper.vm.getHashtagsRecommendationHintId()).toEqual('hashtags_recommendation_hint_test');
        });
    });
});
