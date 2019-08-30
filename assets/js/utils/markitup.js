import markitup from 'markitup';

const markitupSet = {
    preview: false,
    afterInsert: function() {
        this.textarea.dispatchEvent(new Event('change'));
    },
    tabs: '    ',
    previewRefreshOn: ['markitup.insertion', 'keyup'],
    shortcuts: {},
    toolbar: [
        {
            name: 'Link',
            icon: 'link',
            shortcut: 'Ctrl Shift L',
            before: '[url=]',
            after: '[/url]',
        },
        {
            name: 'Picture',
            icon: 'picture',
            shortcut: 'Ctrl Shift P',
            before: '[img]',
            after: '[/img]',
        },
        {
            name: 'Headings',
            icon: 'header',
            dropdown: [
                {
                    name: 'Heading level 1',
                    shortcut: 'Ctrl Shift 1',
                    before: '[h1]',
                    after: '[/h1]\n',
                },
                {
                    name: 'Heading level 2',
                    shortcut: 'Ctrl Shift 2',
                    before: '[h2]',
                    after: '[/h2]\n',
                },
                {
                    name: 'Heading level 3',
                    shortcut: 'Ctrl Shift 3',
                    before: '[h3]',
                    after: '[/h3]\n',
                },
                {
                    name: 'Heading level 4',
                    shortcut: 'Ctrl Shift 4',
                    before: '[h4]',
                    after: '[/h4]\n',
                },
                {
                    name: 'Heading level 5',
                    shortcut: 'Ctrl Shift 5',
                    before: '[h5]',
                    after: '[/h5]\n',
                },
                {
                    name: 'Heading level 6',
                    shortcut: 'Ctrl Shift 6',
                    before: '[h6]',
                    after: '[/h6]\n',
                },
            ],
        },
        {
            name: 'Bold',
            icon: 'bold',
            shortcut: 'Ctrl Shift B',
            before: '[b]',
            after: '[/b]',
        },
        {
            name: 'Italic',
            icon: 'italic',
            shortcut: 'Ctrl Shift I',
            before: '[i]',
            after: '[/i]',
        },
        {
            name: 'Underline',
            icon: 'underline',
            shortcut: 'Ctrl Shift U',
            before: '[u]',
            after: '[/u]',
        },
        {
            name: 'Strikethrough',
            icon: 'strikethrough',
            shortcut: 'Ctrl Shift S',
            before: '[s]',
            after: '[/s]',
        },
        {
            name: 'Unordered list',
            icon: 'list-ul',
            before: '[ul]\n',
            after: '\n[/ul]\n',
            multiline: true,
        },
        {
            name: 'Ordered list',
            icon: 'list-ol',
            before: '[ol]\n',
            after: '\n[/ol]\n',
            multiline: true,
        },
        {
            name: 'List item',
            icon: 'check',
            before: '[li]',
            after: '[/li]',
        },
        {
            name: 'Paragraph',
            icon: 'paragraph',
            before: '[p]',
            after: '[/p]\n',
        },
    ],
};

/**
 * apply markitup plugin to textarea
 * @param {mixed} textarea
 */
function useMarkitup(textarea) {
    markitup(textarea, markitupSet);
}

export {
    useMarkitup,
};