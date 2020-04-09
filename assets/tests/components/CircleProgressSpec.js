import {mount} from '@vue/test-utils';
import CircleProgress from '../../js/components/CircleProgress';

describe('CircleProgress', function() {
    const totalPoints = 18;
    const assertions = [
      {pointsGained: 0, result: 0},
      {pointsGained: 2, result: 11},
      {pointsGained: 18, result: 100},
    ];
    assertions.forEach(({pointsGained, result}) => {
        describe('should calculate percentage correctly', function() {
            it(`should return ${result}%`, function() {
                const wrapper = mount(CircleProgress, {
                    propsData: {
                        pointsGained: pointsGained,
                        totalPoints: totalPoints,
                    },
                });
                expect(wrapper.html().includes(`${result}%`)).to.deep.equal(true);
            });
        });
    });
});
