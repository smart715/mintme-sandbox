<template>
    <div class="row">
        <token-introduction-profile
            container-class="col-12 col-md-7 col-lg-8"
        />
        <token-introduction-statistics
            container-class="col-12 col-md-5 col-lg-4 mt-3 mt-md-0"
        />
        <token-introduction-description
            container-class="col-12 mt-3 mt-3"
        />
    </div>
</template>

<script>
import TokenIntroductionProfile from './TokenIntroductionProfile';
import TokenIntroductionStatistics from './TokenIntroductionStatistics';
import TokenIntroductionDescription from './TokenIntroductionDescription';

export default {
    name: 'TokenIntroduction',
    data() {
        return {};
    },
    components: {
        TokenIntroductionProfile,
        TokenIntroductionStatistics,
        TokenIntroductionDescription,
    },
};
</script>
