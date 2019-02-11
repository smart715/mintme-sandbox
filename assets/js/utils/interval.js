export default {
    intervals: new Set(),

    make(...args) {
        let newInterval = setInterval(...args);
        this.intervals.add(newInterval);
        return newInterval;
    },

    clear(id) {
        this.intervals.delete(id);
        return clearInterval(id);
    },

    clearAll() {
        for (let id of this.intervals) {
            this.clear(id);
        }
    },
};
