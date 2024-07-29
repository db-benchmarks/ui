<script>
export default {
  name: "InitTable",
  props: {
    content: {
      required: true
    }
  }
}
</script>

<template>
    <table class="table init-table">
      <tr>
        <th>Engine</th>
        <th>Execution time</th>
        <th>CPU</th>
        <th>RAM</th>
        <th>Disc</th>
      </tr>

      <template v-for="(row, key) in content">
        <tr :key="key">
          <td>{{ row.engine_name }}:{{ row.version }}</td>
          <td>{{ row.init_time }}</td>
          <td>
            <ul>
              <li><span class="avg">Average:</span> {{ row.metrics.cpu.average }}</li>
              <li><span class="median">Median:</span> {{ row.metrics.cpu.median }}</li>
              <li><span class="95p">95 percentile:</span> {{ row.metrics.cpu["95p"] }}</li>
            </ul>
          </td>
          <td>
            <ul>
              <li><span class="avg">Average:</span> {{ row.metrics.ram.average }}</li>
              <li><span class="median">Median:</span> {{ row.metrics.ram.median }}</li>
              <li><span class="95p">95 percentile:</span> {{ row.metrics.ram["95p"] }}</li>
            </ul>
          </td>
          <td>
            <ul>
              <li><span class="avg">Average:</span> {{ row.metrics.disc.average }}</li>
              <li><span class="median">Median:</span> {{ row.metrics.disc.median }}</li>
              <li><span class="95p">95 percentile:</span> {{ row.metrics.disc["95p"] }}</li>
            </ul>
          </td>
        </tr>
      </template>
    </table>
</template>

<style scoped>
  .init-table td:not(:first-child) {
    border-left: 1px dotted #bbbbbb;
  }
  .init-table ul{
    padding-left: 15px;
  }
</style>