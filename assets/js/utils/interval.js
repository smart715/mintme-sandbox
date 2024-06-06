export default {
    intervals: new Set(),

    make(...args) {
        const newInterval = setInterval(...args);
        this.intervals.add(newInterval);
        return newInterval;
    },

    clear(id) {
        this.intervals.delete(id);
        return clearInterval(id);
    },

    clearAll() {
        for (const id of this.intervals) {
            this.clear(id);
        }
    },
};
