const truncateFunc = function(val, max) {
    return val.length > max ? val.slice(0, max) + '...' : val;
};

const checkElementWidth = function(width, maxWidth, value) {
    if (width > maxWidth) {
        return value.tooltip;
    }

    return '';
};

const dynamicTruncate = function(windowWidth, elementWidth, mediaBreakpoint, textTruncate) {
    const breakpoint = Object.entries(mediaBreakpoint);

    const sizes = breakpoint.map((value) => value[1].width);

    const closestValue = sizes.reduce(function(prev, curr) {
        return (Math.abs(curr - windowWidth) < Math.abs(prev - windowWidth) ? curr : prev);
    });

    const keyWidth = breakpoint.filter((value) => {
        if (value[1].width === closestValue) {
            return value[0];
        }
    });

    textTruncate.tooltip = checkElementWidth(
        elementWidth,
        mediaBreakpoint[keyWidth[0][0]].elementWidth,
        textTruncate
    );

    return textTruncate;
};
const truncateMiddleFunc = function(val, max) {
    return val.length > max * 2
        ? `${val.slice(0, max)} ... ${val.slice(-max)}`
        : val;
};

const getLimitTruncateFunc = function(windowWidth, breakpoints) {
    const closestValue = breakpoints.reduce((prev, curr) =>
        Math.abs(curr.width - windowWidth) < Math.abs(prev.width - windowWidth) ? curr : prev
    );

    return closestValue.truncateLimit;
};

export default {
    methods: {
        truncateFunc: truncateFunc,
        dynamicTruncate: dynamicTruncate,
        truncateMiddleFunc: truncateMiddleFunc,
        getLimitTruncateFunc: getLimitTruncateFunc,
    },
    filters: {
        truncate: truncateFunc,
        truncateMiddle: truncateMiddleFunc,
    },
};
