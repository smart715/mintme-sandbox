import {tabsByNumber, tabs} from '../../js/utils/constants';

export default {
    props: {
        tabIndex: Number,
    },
    computed: {
        currentTabName() {
            return tabsByNumber[this.tabIndex];
        },
        isIntroTab() {
            return this.currentTabName === tabs.intro;
        },
        isTradeTab() {
            return this.currentTabName === tabs.trade;
        },
        isVotingTab() {
            return [tabs.voting, tabs.create_voting, tabs.show_voting].includes(this.currentTabName);
        },
        isPostTab() {
            return [tabs.post, tabs.posts].includes(this.currentTabName);
        },
        isShowVotingTab() {
            return this.currentTabName === tabs.show_voting;
        },
    },
};
