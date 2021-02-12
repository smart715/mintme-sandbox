<template>
    <div>
        <modal
            :visible="visible"
            @close="$emit('close')"
            no-close
        >
            <template v-slot:body>
                <div class="overflow-wrap-break-word">
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_1.part_1', translationsContext) }}
                        <a :href="tokenLink" target="_blank">
                            {{ tokenLink }}
                        </a>
                        <copy-link :content-to-copy="tokenLink" class="c-pointer mx-1">
                            <font-awesome-icon :icon="['far', 'copy']"/>
                        </copy-link>
                        {{ $t('token.intro.created_modal.paragraph_1.part_2') }}
                    </p>
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_2.part_1') }}
                        <a :href="buyLink" target="_blank">
                            {{ buyLink }}
                        </a>
                        <copy-link :content-to-copy="buyLink" class="c-pointer mx-1">
                            <font-awesome-icon :icon="['far', 'copy']"/>
                        </copy-link>
                        {{ $t('token.intro.created_modal.paragraph_2.part_2') }}
                    </p>
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_3') }}
                    </p>
                    <ul>
                        <li>
                            <a
                                href="https://www.mintme.com/kb/Time-for-token-sales-how-can-I-make-a-difference"
                                target="_blank"
                            >
                                {{ $t('token.intro.created_modal.list_1.item_1') }}
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://www.mintme.com/kb/Aiming-at-a-strong-token"
                                target="_blank"
                            >
                                {{ $t('token.intro.created_modal.list_1.item_2') }}
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://www.mintme.com/kb/Ideas-to-promote-and-sell-your-token"
                                target="_blank"
                            >
                                {{ $t('token.intro.created_modal.list_1.item_3') }}
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://www.mintme.com/kb/Stuck-not-knowing-what-to-do-next"
                                target="_blank"
                            >
                                {{ $t('token.intro.created_modal.list_1.item_4') }}
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://www.mintme.com/kb/Talking-to-your-followers-about-MintMe-we-got-some-ideas"
                                target="_blank"
                            >
                                {{ $t('token.intro.created_modal.list_1.item_5') }}
                            </a>
                        </li>
                    </ul>
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_4') }}
                    </p>
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_5') }}
                    </p>
                    <ol>
                        <li>
                            {{ $t('token.intro.created_modal.list_2.item_1') }}
                        </li>
                        <li>
                            {{ $t('token.intro.created_modal.list_2.item_2') }}
                        </li>
                    </ol>
                    <p>
                        {{ $t('token.intro.created_modal.paragraph_6.part_1') }}
                        <a
                            href="https://www.mintme.com/kb/How-to-deploy-my-token-to-the-blockchain"
                            target="_blank"
                        >
                            {{ $t('token.intro.created_modal.paragraph_6.part_2') }}
                        </a>
                        {{ $t('token.intro.created_modal.paragraph_6.part_3') }}
                    </p>
                </div>
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
        translationsContext: function() {
            return {
                tokenName: this.tokenName,
            };
        },
        tokenLink() {
            return this.$routing.generate('token_show', {name: this.tokenName}, true);
        },
        buyLink() {
            return this.$routing.generate('token_show', {name: this.tokenName, tab: 'buy'}, true);
        },
    },
};
</script>
