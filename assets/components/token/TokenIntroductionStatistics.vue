<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Statistics
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div>
                            Date of creation: 07/2018
                        </div>
                        <div class="pt-4">
                            <div class="pb-1">
                                Wallet on exchange: xxx
                            </div>
                            <div class="pb-1">
                                Active orders: xxx
                            </div>
                            <div class="pb-1">
                                Withdrawn: xxx
                            </div>
                            <div>
                                Sold on the market: xxx
                            </div>
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
