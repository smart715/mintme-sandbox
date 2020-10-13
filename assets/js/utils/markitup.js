import markitup from 'markitup';

const markitupSet = {
    preview: false,
    afterInsert: function() {
        this.textarea.dispatchEvent(new Event('change'));
    },
    tabs: '    ',
    previewRefreshOn: ['markitup.insertion', 'keyup'],
    shortcuts: {
        'tab': function() {
            document.getElementsByTagName('textarea').blur();
        },
        'Shift Tab': function() {
            document.getElementsByTagName('textarea').blur();
        },
    },
    toolbar: [
        {
            name: window.translations ? window.translations['utils.markitup.link'] : '',
            icon: 'link',
            shortcut: 'Ctrl Shift L',
            before: '[url=]',
            after: '[/url]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.picture'] : '',
            icon: 'picture',
            shortcut: 'Ctrl Shift P',
            before: '[img]',
            after: '[/img]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.headings'] : '',
            icon: 'header',
            dropdown: [
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 1) : '',
                    shortcut: 'Ctrl Shift 1',
                    before: '[h1]',
                    after: '[/h1]\n',
                },
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 2) : '',
                    shortcut: 'Ctrl Shift 2',
                    before: '[h2]',
                    after: '[/h2]\n',
                },
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 3) : '',
                    shortcut: 'Ctrl Shift 3',
                    before: '[h3]',
                    after: '[/h3]\n',
                },
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 4) : '',
                    shortcut: 'Ctrl Shift 4',
                    before: '[h4]',
                    after: '[/h4]\n',
                },
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 5) : '',
                    shortcut: 'Ctrl Shift 5',
                    before: '[h5]',
                    after: '[/h5]\n',
                },
                {
                    name: window.translations ? window.translations['utils.markitup.heading_level'].replace('%level%', 6) : '',
                    shortcut: 'Ctrl Shift 6',
                    before: '[h6]',
                    after: '[/h6]\n',
                },
            ],
        },
        {
            name: window.translations ? window.translations['utils.markitup.bold'] : '',
            icon: 'bold',
            shortcut: 'Ctrl Shift B',
            before: '[b]',
            after: '[/b]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.italic'] : '',
            icon: 'italic',
            shortcut: 'Ctrl Shift I',
            before: '[i]',
            after: '[/i]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.underline'] : '',
            icon: 'underline',
            shortcut: 'Ctrl Shift U',
            before: '[u]',
            after: '[/u]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.strikethrough'] : '',
            icon: 'strikethrough',
            shortcut: 'Ctrl Shift S',
            before: '[s]',
            after: '[/s]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.unordered_list'] : '',
            icon: 'list-ul',
            before: '[ul]\n',
            after: '\n[/ul]\n',
            multiline: true,
        },
        {
            name: window.translations ? window.translations['utils.markitup.ordered_list'] : '',
            icon: 'list-ol',
            before: '[ol]\n',
            after: '\n[/ol]\n',
            multiline: true,
        },
        {
            name: window.translations ? window.translations['utils.markitup.list_item'] : '',
            icon: 'check',
            before: '[li]',
            after: '[/li]',
        },
        {
            name: window.translations ? window.translations['utils.markitup.paragraph'] : '',
            icon: 'paragraph',
            before: '[p]',
            after: '[/p]\n',
        },
        {
          name: window.translations ? window.translations['utils.markitup.youtube'] : '',
          icon: 'indent',
          before: '[yt]',
          after: '[/yt]',
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
