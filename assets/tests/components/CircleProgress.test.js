import {shallowMount} from '@vue/test-utils';
import CircleProgress from '../../js/components/CircleProgress';

describe('CircleProgress', () => {
    const totalPoints = 18;
    const assertions = [
      {pointsGained: 0, result: 0},
      {pointsGained: 2, result: 11},
      {pointsGained: 18, result: 100},
    ];
    assertions.forEach(({pointsGained, result}) => {
        describe('should calculate percentage correctly', () => {
            it(`should return ${result}%`, () => {
                const wrapper = shallowMount(CircleProgress, {
                    propsData: {
                        pointsGained: pointsGained,
                        totalPoints: totalPoints,
                    },
                });
                expect(wrapper.html().includes(`progress="${result}"`)).toBe(true);
            });
        });
    });
});
