import Vue from 'vue';

/**
 * helper function that mounts and returns the Component Instances
 * @param {object} component to test.
 * @param {object} options properties to be pass by default
 * @return {object} The Component Instances.
 */
export function mount(component, options) {
    const Constructor = Vue.extend(component);
    return new Constructor(options).$mount();
}
