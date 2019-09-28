import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {mount} from '@vue/test-utils';
import TokenChangeName from '../../js/components/token/TokenChangeName';
Vue.use(Vuelidate);
Vue.use(Toasted);

describe('TokenChangeName', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
            },
        });

        expect(wrapper.vm.currentName).to.equal('foobar');
        expect(wrapper.vm.newName).to.equal('foobar');
        expect(wrapper.find('input').element.value).to.equal('foobar');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = true;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = false;
        expect(wrapper.find('button').attributes('disabled')).to.equal('disabled');

        wrapper.vm.isTokenExchanged = false;
        wrapper.vm.isTokenNotDeployed = true;
        expect(wrapper.find('button').attributes('disabled')).to.equal(undefined);
    });

    it('open TwoFactorModal for saving name when 2fa is enabled', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: true,
                isTokenExchanged: false,
                isTokenNotDeployed: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.equal(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).to.equal(true);
    });

    it('do not open TwoFactorModal for saving name when 2fa is disabled', () => {
        const wrapper = mount(TokenChangeName, {
            propsData: {
                currentName: 'foobar',
                twofa: false,
                isTokenExchanged: false,
                isTokenNotDeployed: true,
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.vm.newName = 'newName';
        wrapper.vm.editName();
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
    });

    describe('throw error', () => {
        it('when token name has invalid chars in the beginning', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('  ---newName');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.$v.newName.validFirstChars).to.deep.equal(true);
        });

        it('when token name has invalid chars in the end', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('newName----  ');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.$v.newName.validEndChars).to.deep.equal(true);
        });

        it('when token name has spaces between dashes', () => {
            const wrapper = mount(TokenChangeName, {
                propsData: {currentName: 'foobar'},
            });
            wrapper.find('input').setValue('new--- ---Name');
            wrapper.vm.editName();
            wrapper.vm.$v.$touch();
            expect(wrapper.vm.$v.newName.noSpaceBetweenDashes).to.deep.equal(true);
        });
    });
});
