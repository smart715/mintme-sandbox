export default (data) => {
    return {
        clipboardData: {
            getData() {
                return data;
            },
        },
    };
};
