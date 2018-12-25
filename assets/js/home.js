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
onresize = setHomepageDescriptionWidth;

import CountDown from '../components/CountDown.vue';

new Vue({
    el: '#home',
    components: {
        CountDown,
    },
});
