import {TEXT_HASHTAG} from '../../utils/regex';
import {NotificationMixin} from '../../mixins';
import {debounce} from 'lodash';
import axios from 'axios';
import {KeyCode} from '../../utils/constants';

export default {
    mixins: [
        NotificationMixin,
    ],
    data() {
        return {
            foundHashtags: [],
            hashtagsAxiosCancelTokenSource: null,
            debouncedGetHashtags: debounce(this.getHashtags, 300),
            lastFocusedEl: null,
        };
    },
    methods: {
        async getHashtags(hashtag) {
            this.cancelHashtagsRequest();

            this.hashtagsAxiosCancelTokenSource = axios.CancelToken.source();

            try {
                const response = await this.$axios.single.get(
                    this.$routing.generate('search_hashtags', {query: hashtag}),
                    {cancelToken: this.hashtagsAxiosCancelTokenSource.token},
                );

                this.foundHashtags = response.data;

                this.$nextTick(() => this.fixHintsContainerBoundaries());
            } catch (err) {
                if (axios.isCancel(err)) {
                    return;
                }

                this.foundHashtags = [];

                this.$logger.error('Error during searching for hashtag', err);
            }
        },
        proceedHashtags(text, escapeTags = true) {
            const tagsToReplace = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
            };

            if (escapeTags) {
                text = text.replace(/[&<>]/g, (tag) => tagsToReplace[tag] || tag);
            }

            return text.replace(
                TEXT_HASHTAG,
                `</span><span class="text-primary">#$1</span><span>`
            );
        },
        proceedText(el) {
            if (!el.childNodes?.length) {
                el.innerHTML = '<div><br></div>';

                return;
            }

            Array.from(el.childNodes).forEach((element) => {
                if ('BR' === element.tagName && 1 === el.childNodes.length) {
                    el.innerHTML = '<div><br></div>';
                } else {
                    element.innerHTML = !element.textContent ? '<br>' : this.proceedHashtags(element.textContent);
                }
            });
        },
        getTextFromHtml(el) {
            const tagsToReplace = {
                '&amp;': '&',
                '&lt;': '<',
                '&gt;': '>',
            };

            let result = Array.from(el.childNodes).reduce((acc, node, index) => {
                acc += (0 !== index && node instanceof HTMLDivElement ? '\n' : '') + node.textContent;

                return acc;
            }, '');

            result = result.replace(/&amp;|&lt;|&gt;/g, (tag) => tagsToReplace[tag] || tag);

            return result;
        },
        getHtmlFromText(text) {
            return this.proceedHashtags(text, false).split('\n')
                .map((val) => '<div>' + (val ? val : '<br>') + '</div>').join('');
        },
        onEditablePaste(event) {
            event.preventDefault();
            // deprecation causes no problems
            document.execCommand('inserttext', false, event.clipboardData.getData('text/plain'));
        },
        onEditableBlockInput(newTagInserted = false) {
            const sel = window.getSelection();
            const node = sel.focusNode;
            const offset = sel.focusOffset;
            const pos = this.getCursorPosition(this.$refs['editable'], node, offset, {pos: 0, done: false});

            if (0 === offset) { // avoid getting stuck on zero position
                pos.pos += 0.5;
            }

            this.proceedText(this.$refs['editable']);

            this.localValue = this.getTextFromHtml(this.$refs['editable']);
            this.onInput();

            sel.removeAllRanges();
            const range = this.setCursorPosition(this.$refs['editable'], document.createRange(), {
                pos: pos.pos,
                done: false,
            }, newTagInserted);
            range.collapse(true);

            if (range.startContainer === document) {
                range.setStart(this.$refs['editable'].childNodes[0], 0);
            }

            sel.addRange(range);

            this.handleHashtagRecommendations();
        },
        getCursorPosition(parent, node, offset, stat) {
            if (stat.done) {
                return stat;
            }

            let currentNode = null;
            if (0 === parent.childNodes.length) {
                stat.pos += '' === parent.textContent ? 1 : parent.textContent.length;
            } else {
                for (let i = 0; i < parent.childNodes.length && !stat.done; i++) {
                    currentNode = parent.childNodes[i];

                    if (currentNode === node) {
                        stat.pos += offset;
                        stat.done = true;

                        return stat;
                    } else {
                        this.getCursorPosition(currentNode, node, offset, stat);
                    }
                }
            }

            return stat;
        },
        setCursorPosition(parent, range, stat, newTagInserted) {
            if (stat.done) {
                return range;
            }

            if (0 === parent.childNodes.length) {
                if (parent.textContent.length >= stat.pos) {
                    if (newTagInserted && parent?.parentElement?.nextElementSibling?.childNodes[0]) {
                        range.setStart(parent.parentElement.nextElementSibling.childNodes[0], 1);
                    } else {
                        range.setStart(parent, stat.pos);
                    }

                    stat.done = true;
                } else if (0.5 === stat.pos && '' === parent.textContent) {
                    range.setStart(parent, 0);
                    stat.done = true;
                } else {
                    stat.pos = stat.pos - ('' === parent.textContent ? 1 : parent.textContent.length);
                }
            } else {
                for (let i = 0; i < parent.childNodes.length && !stat.done; i++) {
                    this.setCursorPosition(parent.childNodes[i], range, stat, newTagInserted);
                }
            }

            return range;
        },
        handleContentEditableValueWatch() {
            if (this.editable && this.localValue !== this.getTextFromHtml(this.$refs['editable'])) {
                this.$refs['editable'].innerHTML = this.getHtmlFromText(this.localValue);
            }
        },
        handleHashtagRecommendations() {
            const focusedNode = window.getSelection().focusNode;

            if (this.isHashtagNode(focusedNode)) {
                const hintsEl = document.getElementById(this.getHashtagsRecommendationHintId());
                const y = focusedNode.parentElement.getBoundingClientRect().top + window.scrollY;
                const parentY = hintsEl.parentElement.getBoundingClientRect().top + window.scrollY;
                const x = focusedNode.parentElement.getBoundingClientRect().left + window.scrollX;
                const parentX = hintsEl.parentElement.getBoundingClientRect().left + window.scrollX;

                hintsEl.style.top = y - parentY + 24 + 'px';
                hintsEl.style.left = x - parentX + 'px';

                this.lastFocusedEl = focusedNode;
                this.debouncedGetHashtags(focusedNode.textContent.replace('#', ''));
            } else {
                this.cancelHashtagsRequest();
                this.foundHashtags = [];
            }
        },
        chooseHashtag(event, hashtag) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const focusedNode = this.lastFocusedEl;

            if (this.isHashtagNode(focusedNode)) {
                focusedNode.nodeValue = '#' + hashtag + ' ';
                this.onEditableBlockInput(true);
            }

            // return false to stop event propagation
            return false;
        },
        cancelHashtagsRequest() {
            if (this.hashtagsAxiosCancelTokenSource) {
                this.hashtagsAxiosCancelTokenSource.cancel();
            }
        },
        onEditableBlockClick() {
            if (window.getSelection().focusNode === this.lastFocusedEl) {
                return;
            }

            this.cancelHashtagsRequest();
            this.foundHashtags = [];
        },
        onEditableBlockBlur() {
            this.cancelHashtagsRequest();
            this.foundHashtags = [];
        },
        onEditableKeyDown(event) {
            if (KeyCode.Tab === event.keyCode && 0 < this.foundHashtags?.length) {
                event.preventDefault();
                this.chooseHashtag(null, this.foundHashtags[0].value);
            }
        },
        fixHintsContainerBoundaries() {
            const hintsEl = document.getElementById(this.getHashtagsRecommendationHintId());

            if (!hintsEl) {
                return;
            }

            const elHeight = hintsEl.offsetHeight;
            const elWidth = hintsEl.offsetWidth;
            const boundingRect = hintsEl.getBoundingClientRect();
            const wHeight = window.innerHeight;
            const wWidth = window.innerWidth;

            // adjust height so it doesn't overflow window
            if (boundingRect.top + elHeight > wHeight) {
                hintsEl.style.height = (wHeight - boundingRect.top - 4) + 'px';
            } else {
                hintsEl.style.height = 'auto';
            }

            // adjust left offset so it doesn't overflow window
            if (boundingRect.left + elWidth > wWidth) {
                hintsEl.style.left = Math.max(
                    parseFloat(hintsEl.style.left || 0) - boundingRect.left - elWidth + wWidth - 16,
                    0,
                ) + 'px';
            }
        },
        isHashtagNode(node) {
            return node
                && node.parentElement
                && node.parentElement.classList
                && 'text-primary' === node.parentElement.classList[0];
        },
        getHashtagsRecommendationHintId() {
            return `hashtags_recommendation_hint_${this.textareaId}`;
        },
    },
    mounted() {
        if (this.editable) {
            this.$refs['editable'].innerHTML = this.getHtmlFromText(this.localValue);
        }
    },
};
