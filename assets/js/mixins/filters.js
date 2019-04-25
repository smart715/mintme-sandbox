const truncateFunc = function(val, max) {
    return val.length > max ? val.slice(0, max) + '..' : val;
};

export default {
    methods: {
        truncateFunc: truncateFunc,
    },
    filters: {
        truncate: truncateFunc,
    },
};
