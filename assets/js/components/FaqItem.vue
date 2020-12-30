<template>
    <b-card no-body class="w-100">
        <b-card-header header-tag="div" class="p-1" role="tab">
            <b-btn block href="#" v-b-toggle="identifier" variant="link" class="text-white text-left">
                <slot name="title">{{ $t('faq_item.title') }}</slot>
                <font-awesome-icon :icon="icon"  class="float-right"></font-awesome-icon>
            </b-btn>
        </b-card-header>
        <b-collapse @show="switchIcon" @hide="switchIcon" :id="identifier" :accordion="groupName" role="tabpanel">
            <b-card-body class="pt-1 pb-1">
                <p class="card-text">
                    <slot name="body">
                        {{ $t('faq_item.body.1') }} <code>{{ $t('faq_item.body.2') }}</code> {{ $t('faq_item.body.3') }} <code>{{ $t('faq_item.body.4') }}</code>
                    </slot>
                </p>
            </b-card-body>
        </b-collapse>
    </b-card>
</template>

<script>

export default {
    name: 'FaqItem',
    props: {
        groupName: {type: String, default: 'faq-accordion'},
    },
    data: function() {
          return {
              icon: 'chevron-down',
          };
      },
      computed: {
          identifier: function() {
              return 'faq-' + Math.floor(Math.random() * 100) + Date.now();
          },
      },
      methods: {
          switchIcon: function() {
              switch (this.icon) {
                  case 'chevron-down': this.icon = 'chevron-up'; break;
                  case 'chevron-up': this.icon = 'chevron-down'; break;
              }
              this.$emit('switch');
          },
      },
};
</script>
