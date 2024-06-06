<template>
    <div>
        <ul class="list-group">
            <li
                class="list-group-item background-list c-pointer rounded mt-4"
                @click="goToShow"
            >
                <p class="text-white mb-2 d-flex">
                    <span class="font-italic font-size-1 align-self-center mt-2 mr-1">
                        {{ status }}
                    </span>
                    <span class="text-primary item-title">
                        {{ proposition.title }}
                    </span>
                    <span class="align-self-center ml-auto">
                        <a
                            v-if="showDeleteIcon"
                            class="delete-icon"
                            @click.stop="openDeletePropositionModal(proposition)"
                        >
                            <font-awesome-icon
                                class="icon-default c-pointer align-middle"
                                icon="trash"
                            />
                        </a>
                    </span>
                </p>
                <p>
                    <span>{{ this.$t('voting.proposition.info.by') }}</span>
                    <img
                        :src="info.img"
                        class="rounded-circle avatar-img"
                        alt="avatar"
                    />
                    <span class="font-weight-bold">{{ info.nickname }}</span>
                    <span class="mx-1">|</span>
                    <span>{{ this.$t('voting.proposition.info.start') }}</span>
                    <span class="font-weight-bold">{{ info.startDate }} - </span>
                    <span>{{ this.$t('voting.proposition.info.end') }}</span>
                    <span class="font-weight-bold">{{ info.endDate }}</span>
                    <span class="mx-1">|</span>
                    <span>{{ this.$t('voting.proposition.voters') }}</span>
                    <span class="font-weight-bold">{{ votesCount }}</span>
                </p>
            </li>
        </ul>
        <confirm-modal
            type="delete"
            :visible="showDeletePropositionModal"
            :submitting="isDeleting"
            :show-image="false"
            :close-on-confirm="false"
            @confirm="deleteProposition"
            @close="closeDeletePropositionModal"
        >
            <p class="text-white modal-title pt-2">
                {{ $t('voting.delete.confirm.body', translationContext) }}
            </p>
            <template v-slot:confirm>
                {{ $t('confirm_modal.delete') }}
            </template>
        </confirm-modal>
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import {GENERAL} from '../../utils/constants';
import moment from 'moment';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTrash} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import ConfirmModal from '../modal/ConfirmModal';
import {NotificationMixin} from '../../mixins';

library.add(faTrash);

export default {
    name: 'VotingProposition',
    mixins: [NotificationMixin],
    components: {
        FontAwesomeIcon,
        ConfirmModal,
    },
    props: {
        proposition: Object,
        isTokenPage: Boolean,
        isOwner: Boolean,
    },
    data() {
        return {
            showDeletePropositionModal: false,
            isDeleting: false,
            activeProposition: null,
        };
    },
    computed: {
        ...mapGetters('voting', {
            tokenName: 'getTokenName',
        }),
        ...mapGetters('user', {
            userId: 'getId',
        }),
        info() {
            return ({
                nickname: this.proposition.creatorProfile.nickname,
                img: this.proposition.creatorProfile.image.avatar_small,
                startDate: moment(this.proposition.createdAt).format(GENERAL.dateTimeFormat),
                endDate: moment(this.proposition.endDate).format(GENERAL.dateTimeFormat),
            });
        },
        status() {
            return this.proposition.closed
                ? this.$t('voting.proposition.closed')
                : this.$t('voting.proposition.active');
        },
        votesCount() {
            return this.proposition.userVotings.length;
        },
        showDeleteIcon() {
            return this.isTokenPage && (this.isOwner || this.proposition.creatorId === this.userId);
        },
        translationContext() {
            return {
                title: this.proposition.title,
            };
        },
    },
    methods: {
        ...mapMutations('voting', [
            'setCurrentVoting',
        ]),
        goToShow() {
            this.setCurrentVoting(this.proposition);
            this.$emit('go-to-show');
        },
        deleteProposition: async function() {
            this.isDeleting = true;

            try {
                await this.$axios.single.delete(this.$routing.generate(
                    'delete_voting',
                    {id: this.activeProposition.id}
                ));

                this.notifySuccess(this.$t('voting.deleted'));
                this.$emit('proposition-deleted', this.activeProposition);
                this.activeProposition = null;
                this.closeDeletePropositionModal();
            } catch (error) {
                this.$logger.error('Error while get token networks', error);
                this.notifyError(this.$t('voting.error.deleted'));
            } finally {
                this.isDeleting = false;
            }
        },
        openDeletePropositionModal(proposition) {
            this.activeProposition = proposition;
            this.showDeletePropositionModal = true;
        },
        closeDeletePropositionModal() {
            this.showDeletePropositionModal = false;
        },
    },
};
</script>
