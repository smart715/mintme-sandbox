import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TimerMixin from '../../js/mixins/timer';

describe('timerMixin', () => {
    it('should be disabled on start', () => {
        const timerName = 'timer-01';
        const Component = Vue.component('foo', {
            template: '<div></div>',
            mixins: [TimerMixin],
        });
        const wrapper = shallowMount(Component, {
            propsData: {
                loggedIn: false,
            },
        });

        expect(wrapper.vm.isTimerActive(timerName)).toBe(false);
        expect(wrapper.vm.getTimerSeconds(timerName)).toBe(0);
    });

    it('should work correctly', () => {
        const timerName = 'timer-01';
        const Component = Vue.component('foo', {
            template: '<div></div>',
            mixins: [TimerMixin],
        });
        const wrapper = shallowMount(Component, {
            propsData: {
                loggedIn: false,
            },
        });

        wrapper.vm.startTimer(timerName, 60);
        expect(wrapper.vm.isTimerActive(timerName)).toBe(true);
        expect(wrapper.vm.timerSeconds).toBe(60);
        expect(wrapper.vm.getTimerSeconds(timerName)).toBe(61);
    });

    it('start with 2 timers', () => {
        const timerNameA = 'timer-01';
        const timerNameB = 'timer-02';
        const Component = Vue.component('foo', {
            template: '<div></div>',
            mixins: [TimerMixin],
        });
        const wrapper = shallowMount(Component, {
            propsData: {
                loggedIn: false,
            },
        });

        wrapper.vm.startTimer(timerNameA, 60);
        expect(wrapper.vm.isTimerActive(timerNameA)).toBe(true);
        expect(wrapper.vm.getTimerSeconds(timerNameA)).toBe(61);
        wrapper.vm.startTimer(timerNameB, 60);
        expect(wrapper.vm.isTimerActive(timerNameB)).toBe(true);
        expect(wrapper.vm.getTimerSeconds(timerNameB)).toBe(61);
        expect(wrapper.vm.timerSeconds).toBe(60);
    });
});
