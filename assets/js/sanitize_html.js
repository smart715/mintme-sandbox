import sanitizeHtml from 'sanitize-html';

const config = {
    allowedSchemes: ['http', 'https'],
    allowedTags: ['a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'ul', 'ol', 'li', 'p'],
    allowedAttributes: {
        a: ['href', 'rel', 'target'],
        img: [
            'src',
            {
                name: 'style',
                values: ['max-width: 100%;'],
            },
        ],
        span: [
            {
                name: 'style',
                values: [
                    'font-weight: bold;',
                    'text-decoration: underline;',
                    'text-decoration: line-through;',
                    'font-style: italic;',
                ],
            },
        ],
    },
};

const updateComponent = (el, binding) => {
    if (binding.oldValue === binding.value) {
        return;
    }
    el.innerHTML = sanitizeHtml(binding.value, config);
};

export default {
    install(Vue, options) {
        Vue.directive('html-sanitize', {
            inserted: updateComponent,
            update: updateComponent,
        });
    },
};
