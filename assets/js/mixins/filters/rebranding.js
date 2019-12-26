let rebranding = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(Webchain)/g, replacer: 'MintMe Coin'},
        {regexp: /(webchain)/g, replacer: 'mintMe Coin'},
        {regexp: /(WEB)/g, replacer: 'MINTME'},
        {regexp: /(web)/g, replacer: 'MINTME'},
    ];
    brandDict.forEach((item) => {
        if (typeof val !== 'string') {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

export default {
    filters: {
        rebranding: function(val) {
            return rebranding(val);
        },
    },
    methods: {
        rebrandingFunc: function(val) {
            return rebranding(val);
        },
    },
};
