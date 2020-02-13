<template>
    <transition :name="transition">
        <div tabindex="0"
             class="vld-overlay is-active"
             :class="{ 'is-full-page': isFullPage }"
             v-show="isActive"
             :aria-busy="isActive"
             aria-label="Loading"
             :style="{ zIndex: this.zIndex }">
            <div class="vld-background"
                 :style="{ background: this.backgroundColor, opacity: this.opacity }">
            </div>
            <div class="vld-icon">
                <slot name="before"></slot>
                <slot name="default">
                    <component :is="loader" width="width" :height="height"></component>
                </slot>
                <slot name="after"></slot>
            </div>
        </div>
    </transition>

</template>

<script>
  import {removeElement, HTMLElement} from './helpers/Actions.js';

  export default {
    name: 'pageloadspinner',
    props: {
      active: Boolean,
      programmatic: Boolean,
      timeOut: Number,
      container: [Object, Function, HTMLElement],
      isFullPage: {
        type: Boolean,
        default: true,
      },
      transition: {
        type: String,
        default: 'fade',
      },
      image: String,
      backgroundColor: String,
      opacity: Number,
      width: Number,
      height: Number,
      zIndex: Number,
    },
    data: function() {
      return {
        isActive: this.active,
      };
    },
    beforeMount() {
      if (this.programmatic) {
        if (this.container) {
          this.isFullPage = false;
          this.container.appendChild(this.$el);
        } else {
          document.body.appendChild(this.$el);
        }
      }
    },
    mounted() {
      if (this.programmatic) {
        this.isActive = true;
      }
    },
    methods: {
      hide() {
        this.$emit('hide');
        this.$emit('update:active', false);

        if (this.programmatic) {
          this.isActive = false;
          setTimeout(() => {
            this.$destroy();
            removeElement(this.$el);
          }, this.timeOut);
        }
      },
    },
  };
</script>

<style scoped>

    .vld-overlay {
        bottom: 0;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        align-items: center;
        display: none;
        justify-content: center;
        overflow: hidden;
        z-index: 1
    }

    .vld-overlay.is-active {
        display: flex
    }

    .vld-overlay.is-full-page {
        z-index: 999;
        position: fixed
    }

    .vld-overlay .vld-background {
        bottom: 0;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        background: #fff;
        opacity: 0.5
    }

    .vld-icon {
        background-image: url("../../../img/page-load-spinner.gif")
    }

    .vld-overlay .vld-icon, .vld-parent {
        position: relative
    }

</style>
