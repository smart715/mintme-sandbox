<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Statistics
                <span class="card-header-icon text-white">
                    <font-awesome-icon
                        icon="question"
                        class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Profile Statistics
                        </div>
                        <div class="pb-1">
                            Wallet on exchange: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Active orders: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Withdrawn: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Sold on the market: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold pb-4">
                            Token Release Statistics
                        </div>
                        <div class="pb-1">
                            Release period: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Monthly installment: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Already released: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                        <div class="pb-1">
                            Remaining: xxx
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenIntroductionStatistics',
    props: {
        containerClass: String,
    },
    data() {
        return {};
    },
};
</script>
