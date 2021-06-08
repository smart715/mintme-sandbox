import '../../scss/embeded.sass';
import TokenOngoingAirdropCampaign from '../../js/components/token/airdrop_campaign/TokenOngoingAirdropCampaign';
import i18n from '../utils/i18n/i18n';

new Vue({
  el: '#airdrop-embeded',
  components: {
    TokenOngoingAirdropCampaign,
  },
  i18n,
  store,
});
