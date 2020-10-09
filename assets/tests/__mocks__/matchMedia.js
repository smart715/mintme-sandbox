Object.defineProperty(window, 'matchMedia', {
    value: function() {
        return {
            addEventListener: function(){
                //
            },
        };
    },
});
