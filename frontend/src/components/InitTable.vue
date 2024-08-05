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
        <th>Execution time (sec)</th>
        <th>CPU (cores)</th>
        <th>RAM (MB)</th>
        <th>Disc (MB)</th>
      </tr>

      <template v-for="(row, key) in content">
        <tr :key="key">
          <td>{{ row.engine_name }}:{{ row.version }}</td>
          <td>{{ row.init_time }}</td>
          <td>
            <ul>
              <li><span class="avg">Average:</span> {{ row.metrics.cpu.average }}</li>
              <li><span class="median">Median:</span> {{ row.metrics.cpu.median }}</li>
              <li><span class="percentile">95 percentile:</span> {{ row.metrics.cpu["95p"] }}</li>
            </ul>
          </td>
          <td>
            <ul>
              <li><span class="avg">Average:</span> {{ row.metrics.ram.average }}</li>
              <li><span class="median">Median:</span> {{ row.metrics.ram.median }}</li>
              <li><span class="percentile">95 percentile:</span> {{ row.metrics.ram["95p"] }}</li>
            </ul>
          </td>
          <td>
            <ul>
              <li><span class="avg">Total:</span> {{ row.metrics.disc.total }}</li>
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
  .init-table .avg,.median,.percentile{
    font-size: small;
    color: #454545;
  }
</style>