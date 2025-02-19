<script>
export default {
  name: "IngestingTable",
  props: {
    content: {
      required: true
    }
  },
  computed: {
    minExecutionTime() {
      return Math.min(...this.content.map(row => row.init_time));
    },
    minCPU95p() {
      return Math.min(...this.content.map(row => row.metrics.cpu["95p"]));
    },
    minRAM95p() {
      return Math.min(...this.content.map(row => row.metrics.ram["95p"]));
    },
    minIORead() {
      return Math.min(...this.content.map(row => row.metrics.disc.read?.total || Infinity));
    },
    minIOWrite() {
      return Math.min(...this.content.map(row => row.metrics.disc.write?.total || Infinity));
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
    formatHumanReadableMegaBytes: function (mb) {
      const units = ['MB', 'GB', 'TB'];
      let unitIndex = 0;
      while (mb >= 1024 && unitIndex < units.length - 1) {
        mb = mb / 1024;
        unitIndex++;
      }
      return mb.toFixed(2) + ' ' + units[unitIndex];
    }
  }
};
</script>

<template>
  <table class="table init-table">
    <tr>
      <th>Engine</th>
      <th v-tooltip="'Lower is better'">Execution time</th>
      <th v-tooltip="'Lower is better'">CPU LOAD (cores)</th>
      <th v-tooltip="'Lower is better'">RAM</th>
      <th v-tooltip="'Lower is better'">IO (total)</th>
    </tr>

    <template v-for="(row, key) in content">
      <tr :key="key">
        <td>
          <span :class="{'text-bold': row.init_time === minExecutionTime}">
            {{ row.engine_name }}{{ row.type.length > 0 ? "_" + row.type : "" }}:{{ row.version }}
          </span>
        </td>

        <td>
          <span :class="{'text-green': row.init_time === minExecutionTime, 'text-red': true}">
            {{ formatHumanReadableTime(parseInt(row.init_time)) }}
          </span>
        </td>

        <!-- CPU Load -->
        <td>
          <ul>
            <li><span class="avg">Average:</span> {{ row.metrics.cpu.average }}</li>
            <li><span class="median">Median:</span> {{ row.metrics.cpu.median }}</li>
            <li :class="{'text-green': row.metrics.cpu['95p'] === minCPU95p, 'text-red': true}">
              <span class="percentile">95 percentile:</span>
              <span>
                {{ row.metrics.cpu["95p"] }}
              </span>
            </li>
          </ul>
        </td>

        <!-- RAM Load -->
        <td>
          <ul>
            <li><span class="avg">Average:</span> {{ formatHumanReadableMegaBytes(row.metrics.ram.average) }}</li>
            <li><span class="median">Median:</span> {{ formatHumanReadableMegaBytes(row.metrics.ram.median) }}</li>
            <li>
              <span class="percentile">95 percentile:</span>
              <span :class="{'text-green': row.metrics.ram['95p'] === minRAM95p, 'text-red': true}">
                {{ formatHumanReadableMegaBytes(row.metrics.ram["95p"]) }}
              </span>
            </li>
          </ul>
        </td>

        <!-- IO Read/Write -->
        <td>
          <ul>
            <template v-if="row.metrics.disc.read === undefined">
              <li><span class="avg">Total (r+w):</span> {{ formatHumanReadableMegaBytes(row.metrics.disc.total) }}</li>
            </template>
            <template v-else>
              <li>
                <span class="avg">Read:</span>
                <span :class="{'text-green': row.metrics.disc.read.total === minIORead, 'text-red': true}">
                  {{ formatHumanReadableMegaBytes(row.metrics.disc.read.total) }}
                </span>
              </li>
              <li>
                <span class="avg">Write:</span>
                <span :class="{'text-green': row.metrics.disc.write.total === minIOWrite, 'text-red': true}">
                  {{ formatHumanReadableMegaBytes(row.metrics.disc.write.total) }}
                </span>
              </li>
            </template>
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

.text-red {
  color: red;
}

.text-green{
  color: green;
}

.text-bold{
  font-weight: bold;
}
</style>