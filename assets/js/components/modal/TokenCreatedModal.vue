<template>
    <div>
        <modal
            :visible="visible"
            @close="$emit('close')"
            no-close
        >
            <template v-slot:body>
                <p>
                    Congratulations on creating your own token {{ tokenName }}. Next step is to message all people you know with
                    <a :href="tokenLink" target="_blank">
                        link to your token
                    </a>
                    <copy-link :content-to-copy="tokenLink" class="c-pointer mx-1">
                        <font-awesome-icon :icon="['far', 'copy']"/>
                    </copy-link>
                    asking for support. You might also use airdrop feature and give away your coins in small amounts in exchange for advertising it. Consider messaging people you don't know but be prepared to have strong arguments about why they should buy your token.
                </p>
                <p>
                    Users buying tokens through
                    <a :href="buyLink" target="_blank">
                        this link
                    </a>
                    <copy-link :content-to-copy="buyLink" class="c-pointer mx-1">
                        <font-awesome-icon :icon="['far', 'copy']"/>
                    </copy-link>
                    purchase it only from you, contrary to trade option. Remember that marketing your token is up to you so start sharing link to your token now and good luck!
                </p>
                <p>
                    Also, check our articles with marketing tips:
                    <ul>
                        <li>
                            Time for token sales, how can I make a difference?
                        </li>
                        <li>
                            Aiming at a strong token?
                        </li>
                        <li>
                            Ideas to promote and sell your token
                        </li>
                        <li>
                            Stuck not knowing what to do next?
                        </li>
                        <li>
                            Talking to your followers about MintMe? We got some ideas!
                        </li>
                    </ul>
                </p>
                <p>
                    For better results, you need to constantly educate people when bringing them in so don't forget about this!
                </p>
                <p>
                    Please consider deploying your token for which you will get following benefits:
                </p>
                <ol>
                    <li>
                        It can be withdrawn and deposited
                    </li>
                    <li>
                        Better position on the trading page
                    </li>
                </ol>
                <p>
                    Read How to deploy my token to the blockchain? article to learn about details.
                </p>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';
import CopyLink from '../CopyLink';

export default {
    name: 'TokenCreatedModal',
    components: {
        CopyLink,
        Modal,
    },
    props: {
        visible: Boolean,
        tokenName: String,
    },
    computed: {
        tokenLink() {
            return this.$routing.generate('token_show', {name: this.tokenName}, true);
        },
        buyLink() {
            return this.$routing.generate('token_show', {name: this.tokenName, tab: 'buy'}, true);
        },
    },
};
</script>
