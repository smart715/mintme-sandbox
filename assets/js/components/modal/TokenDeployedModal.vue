<template>
    <div>
        <modal
            :visible="visible"
            @close="$emit('close')"
            :no-close="false"
        >
            <template v-slot:body>
                <div class="mb-3">
                    <h1 class="text-bold">
                        {{ $t('token.intro.deployed_modal.title') }}
                    </h1>
                </div>
                <div class="overflow-wrap-break-word">
                    <p>
                        {{ $t('token.intro.deployed_modal.paragraph_1.head') }}
                        <ol>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_1.part_1') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_1.part_2') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_1.part_3') }}</li>
                        </ol>
                    </p>
                    <p>
                        {{ $t('token.intro.deployed_modal.paragraph_2.head') }}
                        <ol>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_2.part_1') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_2.part_2') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_2.part_3') }}</li>
                        </ol>
                    </p>
                    <p>
                        {{ $t('token.intro.deployed_modal.paragraph_3.head_1') }}<br>
                        {{ $t('token.intro.deployed_modal.paragraph_3.head_2') }}
                        <ul>
                            <li>
                                <a
                                    :href="`${url}kb/Time-for-token-sales-how-can-I-make-a-difference`"
                                    target="_blank"
                                    class="link highlight"
                                >
                                    {{ $t('token.intro.created_modal.list_1.item_1') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="`${url}kb/Aiming-at-a-strong-token`"
                                    target="_blank"
                                    class="link highlight"
                                >
                                    {{ $t('token.intro.created_modal.list_1.item_2') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="`${url}kb/Ideas-to-promote-and-sell-your-token`"
                                    target="_blank"
                                    class="link highlight"
                                >
                                    {{ $t('token.intro.created_modal.list_1.item_3') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="`${url}kb/Stuck-not-knowing-what-to-do-next`"
                                    target="_blank"
                                    class="link highlight"
                                >
                                    {{ $t('token.intro.created_modal.list_1.item_4') }}
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="`${url}kb/Talking-to-your-followers-about-MintMe-we-got-some-ideas`"
                                    target="_blank"
                                    class="link highlight"
                                >
                                    {{ $t('token.intro.created_modal.list_1.item_5') }}
                                </a>
                            </li>
                        </ul>
                    </p>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';
import {mintmeUrl} from '../../utils/constants';

export default {
    name: 'TokenDeployedModal',
    components: {
        Modal,
    },
    props: {
        visible: {
            type: Boolean,
            default: true,
        },
        tokenName: String,
    },
    data() {
        return {
            url: mintmeUrl,
        };
    },
};
</script>
