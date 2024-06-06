<template>
    <div data-aos="zoom-in">
        <h2
            class="section-header text-center"
            v-html="$t('page.coin.roadmap.header')"
        ></h2>
        <div class="container">
            <div class="row timeline mx-auto">
                <div
                    v-for="(checkPointHeader, checkPointNumber) in checkPointTrans.header"
                    :key="checkPointNumber"
                    :class="firstCheckPointClasses(checkPointNumber)"
                    class="col-12 my-2 my-md-0 p-0 text-left"
                    v-vpshow
                >
                    <h5 class="text-primary">{{ checkPointHeader }}</h5>
                    <ul v-html="checkPointTrans.body[checkPointNumber]"></ul>
                </div>
                <div
                    class="col-12 my-2 my-md-0 p-0 text-left"
                    v-vpshow
                >
                    <h5 class="text-primary">{{ $t('page.coin.roadmap.last_part') }}</h5>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RoadMap',
    data() {
        return {
            checkPointTrans: {
                header: [
                    this.$t('page.coin.roadmap.check_point.header.1'),
                    this.$t('page.coin.roadmap.check_point.header.2'),
                ],
                body: [
                    this.$t('page.coin.roadmap.check_point.body.1'),
                    this.$t('page.coin.roadmap.check_point.body.2'),
                ],
            },
        };
    },
    directives: {
        vpshow: {
            inViewport: function(element) {
                const rect = element.getBoundingClientRect();
                return 0 < window.innerHeight - rect.bottom;
            },
            bind: function(element, binding) {
                element.$onScroll = function() {
                    if (element.classList.contains('road-active') && binding.def.inViewport(element)) {
                        element.classList.add('enter');

                        let nextSibling = element.nextSibling;
                        while (nextSibling && 1 !== nextSibling.nodeType) {
                            nextSibling = nextSibling.nextSibling;
                        }

                        if (nextSibling) {
                            nextSibling.classList.add('road-active-before');
                        }

                        element.addEventListener('transitionend', function() {
                            if ('height' !== event.propertyName || !nextSibling) {
                                return;
                            }

                            if (nextSibling && nextSibling.classList.contains('road-active-before')) {
                                nextSibling.classList.remove('road-active-before');
                                nextSibling.classList.add('road-active');
                                nextSibling.classList.add('road-endpoint');
                            }

                            element.classList.remove('road-endpoint');
                            document.dispatchEvent(new Event('scroll'));
                        });
                        binding.def.unBind(element);
                    }
                };

                document.addEventListener('scroll', element.$onScroll);
            },
            unBind: function(element) {
                document.removeEventListener('scroll', element.$onScroll);
                delete element.$onScroll;
            },
        },
    },
    methods: {
        firstCheckPointClasses: function(checkPointNumber) {
            return {
                'road-active road-endpoint': 0 === checkPointNumber,
            };
        },
    },
};
</script>
