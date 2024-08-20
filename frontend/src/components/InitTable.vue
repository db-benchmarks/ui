<script>
export default {
  name: "InitTable",
  props: {
    content: {
      required: true
    }
  },
  methods: {
    formatHumanReadableTime: function (seconds) {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const remainingSeconds = seconds % 60;
      let result = '';

      if (hours > 0) {
        result += hours + (hours === 1 ? ' hour ' : ' hours ');
      }
      if (minutes > 0) {
        result += minutes + (minutes === 1 ? ' min ' : ' mins ');
      }
      if (remainingSeconds > 0 || seconds === 0) {
        result += remainingSeconds + (remainingSeconds === 1 ? ' sec' : ' secs');
      }

      return result.trim();
    },
    formatHumanReadableMegaBytes: function (mb){
      // Define the size units in megabytes
      const units = ['MB', 'GB', 'TB'];

      let result = '';
      let unitIndex = 0;

      // Convert MB to higher units (GB, TB, etc.)
      while (mb >= 1024 && unitIndex < units.length - 1) {
        mb = mb / 1024;
        unitIndex++;
      }

      // Format the result
      result = mb.toFixed(2) + ' ' + units[unitIndex];

      return result;
    }
  }
}
</script>

<template>
  <table class="table init-table">
    <tr>
      <th>Engine</th>
      <th>Execution time</th>
      <th>CPU LOAD (cores)</th>
      <th>RAM</th>
      <th>Read + Write</th>
    </tr>

    <template v-for="(row, key) in content">
      <tr :key="key">
        <td>{{ row.engine_name }}{{ row.type.length > 0 ? "_" + row.type : "" }}:{{ row.version }}</td>
        <td>{{ formatHumanReadableTime(parseInt(row.init_time)) }}</td>
        <td>
          <ul>
            <li><span class="avg">Average:</span> {{ row.metrics.cpu.average }}</li>
            <li><span class="median">Median:</span> {{ row.metrics.cpu.median }}</li>
            <li><span class="percentile">95 percentile:</span> {{ row.metrics.cpu["95p"] }}</li>
          </ul>
        </td>
        <td>
          <ul>
            <li><span class="avg">Average:</span> {{ formatHumanReadableMegaBytes(row.metrics.ram.average) }}</li>
            <li><span class="median">Median:</span> {{ formatHumanReadableMegaBytes(row.metrics.ram.median) }}</li>
            <li><span class="percentile">95 percentile:</span> {{ formatHumanReadableMegaBytes(row.metrics.ram["95p"]) }}</li>
          </ul>
        </td>
        <td>
          <ul>
            <li><span class="avg">Total:</span> {{ formatHumanReadableMegaBytes(row.metrics.disc.total) }}</li>
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

.init-table ul {
  padding-left: 15px;
}

.init-table .avg, .median, .percentile {
  font-size: small;
  color: #454545;
}
</style>