<template>
    <div>
        <modal
            :visible="true"
            @close="$emit('close')"
            no-close
        >
            <template v-slot:body>
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
                        <ol>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_3.part_1') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_3.part_2') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_3.part_3') }}</li>
                            <li>{{ $t('token.intro.deployed_modal.paragraph_3.part_4') }}</li>
                        </ol>
                    </p>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';

export default {
    name: 'TokenDeployedModal',
    components: {
        Modal,
    },
};
</script>
