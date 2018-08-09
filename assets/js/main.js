import '../scss/main.sass';
import BootstrapVue from 'bootstrap-vue';

window.Vue = require('vue');

Vue.use(BootstrapVue);

Vue.options.delimiters = ['{[', ']}'];

/**
 * Set description width equal to title.
 */
function setHomepageDescriptionWidth() {
    let homeShowcaseTitle =
        document.querySelector('.homepage .top-showcase .title');
    if (homeShowcaseTitle) {
        let titleWidth = homeShowcaseTitle.offsetWidth;
        document
            .querySelector('.homepage .top-showcase .description')
            .style.maxWidth = titleWidth + 'px';
    }
}

setHomepageDescriptionWidth();
window.onresize = function() {
    setHomepageDescriptionWidth();
};

new Vue({
    el: '#navbar',
});
