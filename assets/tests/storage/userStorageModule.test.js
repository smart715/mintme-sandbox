import userModule from '../../js/storage/modules/user';

describe('userStorageModule', () => {
    it('setHasPhoneVerified works correctly', () => {
        expect(userModule.state.hasPhoneVerified).toBe(false);

        userModule.mutations.setHasPhoneVerified(userModule.state, true);

        expect(userModule.getters.getHasPhoneVerified(userModule.state)).toBe(true);
    });
});
