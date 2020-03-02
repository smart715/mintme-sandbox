const storage = {
  namespaced: true,
  state: {
    isVisible: true,
    visibleLevel: 0,
  },
  getters: {
    getIsSpinnerVisible: function(state) {
      return state.isVisible;
    },
  },
  mutations: {
    directShowSpinner: function(state) {
      state.visibleLevel = 0;
      state.isVisible = true;
    },
    showSpinner: function(state) {
      if (!state.visibleLevel) {
        state.isVisible = true;
      }
      state.visibleLevel++;
      alert('Switch on! ' + state.visibleLevel);
    },
    directHideSpinner: function(state) {
      state.visibleLevel = 0;
      state.isVisible = false;
    },
    hideSpinner: function(state) {
      if (state.visibleLevel) {
        state.visibleLevel--;
        alert('Switch off! ' + state.visibleLevel);
      }
      if (!state.visibleLevel) {
        state.isVisible = false;
        alert('Switch off all!');
      }
    },
  },
};

export default storage;
