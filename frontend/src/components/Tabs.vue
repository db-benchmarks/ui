<template>
  <div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li v-for="(selected, name) in items" :key="name" class="nav-item">
        <a :class="'nav-link' + (isFirst(name)?' active':'')" :id="name.replaceAll(' ','-')+'-tab'" data-toggle="tab" :href="'#'+name.replaceAll(' ','-')"
           role="tab"
           :aria-controls="name.replaceAll(' ','-')">{{ name }}</a>
      </li>
    </ul>
    <div class="tab-content" id="myTabContent">
      <div v-for="(selected, name) in items" :key="name"
           :class="'tab-pane fade' +(isFirst(name)?' show active':'')" :id="name.replaceAll(' ','-')" role="tabpanel"
           :aria-labelledby="name.replaceAll(' ','-')+'-tab'">
        <pre class="mt-2" v-html="selected"></pre>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Tabs",
  props: {
    items: {
      required: true
    }
  },
  methods: {
    isFirst(key) {
      return Object.keys(this.items)[0] === key;
    }
  }
}
</script>

<style scoped>
pre {
  white-space: pre-wrap;
  border: 2px solid #fbd07d;
  border-radius: 5px;
  padding: 10px;
  background: #fff5e2;
}
</style>
