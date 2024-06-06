import {shallowMount} from '@vue/test-utils';
import CoinAvatar from '../../js/components/CoinAvatar.vue';

describe('CoinAvatar', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CoinAvatar);
    });

    it('should render an img element if avatarImg is truthy', async () => {
        await wrapper.setProps({
            avatarImg: 'https://example.com/avatar.png',
            isDeployed: true,
        });

        expect(wrapper.findComponent('img').exists()).toBe(true);
    });

    it('should not render an img element if avatarImg is falsy', async () => {
        await wrapper.setProps({
            avatarImg: '',
            isDeployed: false,
        });

        expect(wrapper.findComponent('img').exists()).toBe(false);
    });

    it('should return the correct token icon by symbol', async () => {
        await wrapper.setProps({
            symbol: 'ETH',
            isWhiteColor: true,
        });

        expect(wrapper.vm.getTokenIconBySymbol('ETH')).toBe('ETH_avatar.svg');
    });

    it('should return the correct not user token image if not deployed', async () => {
        await wrapper.setProps({
            symbol: 'BTC',
            isDeployed: false,
        });

        expect(wrapper.vm.avatarImg).toBe('');
    });

    it('should return true for isWebSymbol if symbol is WEB or MINTME', async () => {
        await wrapper.setProps({
            symbol: 'WEB',
        });

        expect(wrapper.vm.isWebSymbol).toBe(true);

        await wrapper.setProps({
            symbol: 'MINTME',
        });

        expect(wrapper.vm.isWebSymbol).toBe(true);
    });

    it('should return false for isWebSymbol if symbol is not WEB or MINTME', async () => {
        await wrapper.setProps({
            symbol: 'BTC',
        });

        expect(wrapper.vm.isWebSymbol).toBe(false);
    });

    it('should return the correct image for a user token', async () => {
        await wrapper.setProps({
            isUserToken: true,
            image: 'https://example.com/token.png',
        });

        expect(wrapper.vm.avatarImg).toEqual('https://example.com/token.png');
    });

    it('should return the correct image for a non-user token', async () => {
        await wrapper.setProps({
            isDeployed: true,
            symbol: 'ETH',
            isWhiteColor: true,
        });

        const expectedImg = require('../../img/eth-avatar-white.png');

        expect(wrapper.vm.avatarImg).toEqual(expectedImg);
    });

    it('should return an empty string if not a user token and not deployed', async () => {
        await wrapper.setProps({
            symbol: 'ETH',
        });

        expect(wrapper.vm.avatarImg).toEqual('');
    });

    it('should return the correct token icon by symbol and color', async () => {
        await wrapper.setProps({
            symbol: 'BTC',
            isGreyColor: true,
        });

        expect(wrapper.vm.getTokenIconBySymbol('BTC')).toEqual('BTC.svg');

        await wrapper.setProps({
            symbol: 'ETH',
            isWhiteColor: true,
        });

        expect(wrapper.vm.getTokenIconBySymbol('ETH')).toEqual('ETH_avatar.svg');
    });

    it('should return the web grey icon name if the symbol is WEB and isGreyColor is true', async () => {
        await wrapper.setProps({
            symbol: 'WEB',
            isGreyColor: true,
        });

        expect(wrapper.vm.getTokenIconBySymbol('WEB')).toBe('WEB_grey.svg');
    });
});
