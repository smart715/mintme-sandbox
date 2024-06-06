import {shallowMount} from '@vue/test-utils';
import CoinAvatarName from '../../js/components/CoinAvatarName';

describe('CoinAvatarName', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CoinAvatarName);
    });

    it('should return tokenDeployed to true when token is deployed', async () => {
        const token = {
            deploymentStatus: 'deployed',
        };

        await wrapper.setProps({token});

        expect(wrapper.vm.tokenDeployed).toBe(true);
    });

    it('should return tokenDeployed to false when token is not deployed', async () => {
        const token = {
            deploymentStatus: 'pending',
        };

        await wrapper.setProps({token});

        expect(wrapper.vm.tokenDeployed).toBe(false);
    });

    it('should return the first network symbol if token is deployed', async () => {
        const token = {
            deploymentStatus: 'deployed',
            networks: [
                'ETH',
                'BSC',
            ],
        };

        await wrapper.setProps({token});

        expect(wrapper.vm.tokenCryptoSymbol).toBe('ETH');
    });

    it('should return tokenCryptoSymbol to null if token is not deployed', async () => {
        const token = {
            deploymentStatus: 'not-deployed',
            networks: [
                'ETH',
                'BSC',
            ],
        };

        await wrapper.setProps({token});

        expect(wrapper.vm.tokenCryptoSymbol).toBeNull();
    });

    it('should return tokenCryptoSymbol to null if token has no networks', async () => {
        const token = {
            deploymentStatus: 'deployed',
            networks: [],
        };

        await wrapper.setProps({token});

        expect(wrapper.vm.tokenCryptoSymbol).toBeNull();
    });
});
