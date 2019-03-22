import Vue from 'vue';
import {FontAwesomeIcon, FontAwesomeLayers} from '@fortawesome/vue-fontawesome';
import component from '../../js/components/token/introduction/TokenIntroductionStatistics';
import {mount} from '../testHelper';
import fontawesome from '@fortawesome/fontawesome';
import fas from '@fortawesome/fontawesome-free-solid';
import far from '@fortawesome/fontawesome-free-regular';
import fab from '@fortawesome/fontawesome-free-brands';
import {faCog, faSearch} from '@fortawesome/free-solid-svg-icons';

fontawesome.library.add(fas, far, fab, faSearch, faCog);

Vue.component('font-awesome-icon', FontAwesomeIcon);
Vue.component('font-awesome-layers', FontAwesomeLayers);

describe('TokenIntroductionStatistics', () => {
    describe('computed field', () => {
        describe(':releasedDisabled', () => {
            it('returns true', () => {
                const obj = mount(component, {
                    propsData: {
                        stats: {
                            releasePeriod: 10,
                            hourlyRate: 1,
                            releasedAmount: 1,
                            frozenAmount: 1,
                        },
                    },
                });

                expect(obj.releasedDisabled).to.be.true;
            });

            it('returns false', () => {
                const obj = mount(component, { });

                expect(obj.releasedDisabled).to.be.false;
            });
        });

        describe(':statsPeriod', () => {
            it('returns {Number}', () => {
                const obj = mount(component, {
                    propsData: {
                        stats: {
                            releasePeriod: 30,
                            hourlyRate: 1,
                            releasedAmount: 1,
                            frozenAmount: 1,
                        },
                    },
                });

                expect(obj.statsPeriod).to.equal(30);
            });

            context('with default object', () => {
                it('returns {Number}', () => {
                    const obj = mount(component, { });

                    expect(obj.statsPeriod).to.equal(10);
                });
            });
        });
    });
});
