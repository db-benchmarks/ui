<template>
  <span class="query-text">
    <template v-for="(segment, index) in segments">
      <span v-if="segment.type === 'text'" :key="'text-' + index">{{ segment.value }}</span>
      <span
        v-else
        :key="'placeholder-' + index"
        class="query-placeholder"
        v-tooltip="segment.tooltip"
      >{{ segment.value }}</span>
    </template>
  </span>
</template>

<script>
export default {
  name: "QueryText",
  props: {
    query: {
      required: true
    }
  },
  computed: {
    normalizedQuery() {
      if (typeof this.query === "string") {
        return {
          text: this.query,
          placeholders: []
        };
      }

      return {
        text: this.query && this.query.text ? this.query.text : "",
        placeholders: this.query && this.query.placeholders ? this.query.placeholders : []
      };
    },
    segments() {
      let text = this.normalizedQuery.text;
      let segments = [];

      for (let placeholder of this.normalizedQuery.placeholders) {
        let index = text.indexOf(placeholder.token);
        if (index === -1) {
          continue;
        }

        if (index > 0) {
          segments.push({
            type: "text",
            value: text.slice(0, index)
          });
        }

        segments.push({
          type: "placeholder",
          value: placeholder.token,
          tooltip: {
            content: placeholder.value,
            classes: "query-tooltip"
          }
        });

        text = text.slice(index + placeholder.token.length);
      }

      if (text.length > 0 || segments.length === 0) {
        segments.push({
          type: "text",
          value: text
        });
      }

      return segments;
    }
  }
}
</script>

<style scoped>
.query-placeholder {
  display: inline-block;
  margin: 0 0.12rem;
  padding: 0.02rem 0.4rem;
  border-radius: 999px;
  background: #eef3f7;
  color: #5f7285;
  font-size: 0.92em;
  line-height: 1.4;
  cursor: help;
}
</style>
