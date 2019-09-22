import {mount} from '@vue/test-utils';
import TokenDeployIcon from '../../js/components/token/deploy/TokenDeployIcon';

describe('TokenDeployIcon', () => {
    it('do not show if owner & not deployed', () => {
        const wrapper = mount(TokenDeployIcon, {
            propsData: {
                isOwner: true,
                statusProp: 'not-deployed',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).to.be.false;
        expect(wrapper.find('.loading-spinner').exists()).to.be.false;
        expect(wrapper.find('.not-deployed-icon').exists()).to.be.true;
    });

    it('do not show if not owner & not deployed', () => {
        const wrapper = mount(TokenDeployIcon, {
            propsData: {
                isOwner: false,
                statusProp: 'not-deployed',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).to.be.false;
        expect(wrapper.find('.loading-spinner').exists()).to.be.false;
        expect(wrapper.find('.not-deployed-icon').exists()).to.be.true;
    });

    it('show pending icon if owner & pending', () => {
        const wrapper = mount(TokenDeployIcon, {
            propsData: {
                isOwner: true,
                statusProp: 'pending',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).to.be.false;
        expect(wrapper.findAll('.loading-spinner').exists()).to.be.true;
        expect(wrapper.find('.not-deployed-icon').exists()).to.be.false;
    });

    it('do not show pending icon if not owner & pending', () => {
        const wrapper = mount(TokenDeployIcon, {
            propsData: {
                isOwner: false,
                statusProp: 'pending',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).to.be.false;
        expect(wrapper.find('.loading-spinner').exists()).to.be.false;
        expect(wrapper.find('.not-deployed-icon').exists()).to.be.false;
    });

    it('show deployed icon if deployed', () => {
        const wrapper = mount(TokenDeployIcon, {
            propsData: {
                statusProp: 'deployed',
            },
        });
        expect(wrapper.find('.deployed-icon').exists()).to.be.true;
        expect(wrapper.find('.loading-spinner').exists()).to.be.false;
        expect(wrapper.find('.not-deployed-icon').exists()).to.be.false;
    });
});
