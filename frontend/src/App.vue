/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>

  <div id="app">
    <div class="container">
      <div class="row">
        <div class="col-12 text-left">
          <img class="d-block mb-1" src="./assets/logo.svg" style="margin-left: auto; margin-right: auto; width=50%;">
          <h4 align="center" dir="auto">
            <a href="/about/">About</a> •
            <a href="/principles/">Testing principles</a> •
            <a href="/framework/">Test framework</a> •
            <a href="/posts/">About tests</a> •
            <a href="https://github.com/db-benchmarks/db-benchmarks">GitHub <img src="./assets/github.png" height="22"
                                                                                 style="vertical-align: middle; padding-bottom: 4px;"/></a>
            •
            <a href="https://twitter.com/db_benchmarks">Twitter<img src="./assets/twitter.png" height="22"
                                                                    style="vertical-align: middle; padding-bottom: 3px; padding-left: 4px;"/></a>
          </h4>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-12 text-left">
          <h1 align="center" style="padding-top: 10px;"><a href="https://github.com/db-benchmarks/db-benchmarks">⭐Star
            us on GitHub⭐</a></h1>
        </div>
      </div>
      <div class="row">
        <div class="col-12 text-left">
          <div align="center">
            <small>Open source is hard. It motivates us a lot.</small>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row mt-2">
        <div class="col-1">
          <h4>Test</h4>
        </div>
        <div class="col-11">
          <ButtonGroup v-bind:items="tests"
                       v-bind:switch="true"
                       v-bind:capitalize="false"
                       v-on:changed="updateMemory(); applySelection(true, true);"/>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col">
          <TestInfo v-bind:test-info="testsInfo[getSelectedRow(tests)]"
                    v-bind:short-server-info="shortServerInfo[getSelectedRow(tests)]"
                    v-bind:full-server-info="fullServerInfo[getSelectedRow(tests)]">
          </TestInfo>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-12">
          <h4>Engines</h4>
          <EngineGroup v-bind:groups="engineGroups"
                       v-bind:items="engines"
                       v-bind:active-items="supportedEngines"
                       v-on:changed="applySelection(false)">
          </EngineGroup>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-5">
          <h4>RAM limit</h4>
          <ButtonGroup v-bind:items="memory"
                       v-bind:switch="true"
                       v-bind:capitalize="false"
                       v-bind:append="'MB'"
                       v-on:changed="applySelection(false, true);"/>
        </div>

        <div class="col-3">
          <h4>Result</h4>
          <ButtonGroup v-bind:items="cache"
                       v-bind:switch="false"
                       v-bind:capitalize="true "
                       v-on:changed="applySelection(false)"/>
        </div>


      </div>

      <div class="row">
        <Table v-bind:engines="getSelectedRow(this.engines)"
               v-bind:cache="prepareCacheForTable()"
               v-bind:results="filteredResults"
               v-bind:check-sum="checksums"
               v-bind:checkedQueries.sync="queries"
               v-on:update:checked="modifyUrl()"
        />
      </div>

      <footer class="my-5 pt-5 text-muted text-center text-small">
      </footer>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Table from "@/components/Table";
import EngineGroup from "@/components/EngineGroup";
import ButtonGroup from "@/components/ButtonGroup";
import TestInfo from "@/components/TestInfo";

export default {
  name: 'App',
  components: {
    TestInfo,
    ButtonGroup,
    EngineGroup,
    Table
  },
  data() {
    return {
      engines: [],
      engineGroups: {},
      results: [],
      filteredResults: [],
      tests: [],
      testsInfo: [],
      shortServerInfo: [],
      fullServerInfo: [],
      memory: [],
      queries: [],
      checksums: {},
      supportedEngines: {},
      resultsCount: 0,
      selectedTest: 0,
      cache: [{"fastest": 0}, {"slowest": 0}, {"fast_avg": 1}],
    }
  },
  created() {
    let serverUrl = process.env.VUE_APP_API_URL;
    if (serverUrl === undefined) {
      serverUrl = '';
    }
    axios.get(serverUrl + "/api").then(response => {
      this.results = response.data.result.data;
      this.tests = response.data.result.tests;
      this.engines = response.data.result.engines;
      this.testsInfo = response.data.result.testsInfo;
      this.shortServerInfo = response.data.result.shortServerInfo;
      this.fullServerInfo = this.parseFullServerInfo(response.data.result.fullServerInfo);
      this.parseUrl();
      this.updateMemory();
      this.parseUrl();
      this.applySelection(false);
    });
  },
  methods: {
    parseFullServerInfo(fullServerInfo){
      let parsed = {}
      for (let testInfo in fullServerInfo){
        parsed[testInfo] = JSON.parse(fullServerInfo[testInfo]);
      }
      return parsed;
    },
    unsetUnavailableEngines() {
      for (let engineIndex in this.engines) {
        let engineName = Object.keys(this.engines[engineIndex])[0];
        if (this.supportedEngines[engineName] === undefined) {
          this.engines[engineIndex][engineName] = 0;
        }
      }
    },
    fillSupportedEngines(data) {
      this.supportedEngines = {};
      for (let dataKey in data) {
        for (let engine in data[dataKey]) {
          if (engine === 'query') {
            continue;
          }
          this.supportedEngines[engine] = 1;
        }
      }
    },
    fillCheckedQueries(data) {

      let result = [];

      for (let selectedQuery in data) {
        result[selectedQuery] = true;
      }

      this.queries = result;
    },
    updateMemory() {
      let memory = [];
      for (let test in this.tests) {
        for (let name in this.tests[test]) {
          if (this.tests[test][name]) {
            let i = 0;
            for (let memValue in this.results[name]) {
              let obj = {};
              if (i === 0) {
                obj[memValue] = 1;
              } else {
                obj[memValue] = 0;
              }
              memory.push(obj)
              i++
            }
          }
        }
      }
      this.memory = memory.sort(function (a, b) {
        if (Object.keys(a)[0] < Object.keys(b)[0]){
          return 1;
        }
        if (Object.keys(a)[0] > Object.keys(b)[0]){
          return -1;
        }

        return 0;
      });
    },
    applySelection(clearQueries = false, unsetUnsupported = false) {
      this.checksums = {};
      let data = this.results[this.getSelectedRow(this.tests)[0]][this.getSelectedRow(this.memory)[0]];
      this.fillSupportedEngines(data);
      if (unsetUnsupported) {
        this.unsetUnavailableEngines();
      }

      let results = [];
      let selectedEngines = this.getSelectedRow(this.engines);


      let selectedCache = this.getSelectedRow(this.cache);
      let i = 0;
      for (let dataKey in data) {
        let row = [data[dataKey]['query']];
        for (let engine in selectedEngines) {

          let checkSum = this.checksums[i];
          if (checkSum === undefined) {
            checkSum = {};
          }

          let checksumValue = -1;
          if (data[dataKey][selectedEngines[engine]] !== undefined) {
            checksumValue = data[dataKey][selectedEngines[engine]]['checksum'];
          }

          checkSum[selectedEngines[engine]] = checksumValue;
          this.checksums[i] = checkSum;

          for (let cache in selectedCache) {
            let value;
            try {
              value = data[dataKey][selectedEngines[engine]][selectedCache[cache]];
            } catch (e) {
              value = '-';
            }
            row = row.concat(value);
          }
        }


        results.push(row)
        i++;
      }

      if (selectedEngines.length === 0) {
        results = [];
      }

      this.fillCheckedQueries(results);

      if (clearQueries) {
        this.modifyUrl();
      }

      this.getSelectionsFromUrl();


      this.modifyUrl();
      this.resultsCount = results.length;
      this.filteredResults = results;
      this.parseEnginesGroups();
    },
    modifyUrl() {
      const params = new URLSearchParams({
        cache: this.getSelectedRow(this.cache),
        engines: this.getSelectedRow(this.engines),
        tests: this.getSelectedRow(this.tests),
        memory: this.getSelectedRow(this.memory),
        queries: this.getSelectedQueries(this.queries),
      });
      window.history.pushState("", "", "/?" + params.toString());
    },
    getUrlParams() {
      return new URLSearchParams(window.location.search);
    },
    parseUrl() {
      const urlParams = this.getUrlParams();

      ['cache', 'engines', 'tests', 'memory'].forEach((row) => {
        let selection = urlParams.get(row);
        if (selection != null && selection !== "") {
          this.setSelection(selection, this[row], row)
        }
      });

    },
    getSelectionsFromUrl() {
      let urlQueries = this.getUrlParams().get('queries');


      if (urlQueries !== null) {
        if (urlQueries !== '') {
          let urlValues = urlQueries.split(",");
          // Clean section
          for (let row in this.queries) {
            this.queries[row] = false;
          }

          for (let selectionid in urlValues) {
            if (this.queries[urlValues[selectionid]] != null) {
              this.queries[urlValues[selectionid]] = true;
            }
          }
        }
      }
    },

    setSelection(urlData, section) {

      // Clean section
      section.forEach((row) => {
        for (let id in row) {
          row[id] = 0;
        }
      });

      // set selection
      let urlValues = urlData.split(",");
      for (let selectionid in urlValues) {
        let currentSelection = urlValues[selectionid];

        section.forEach((row) => {
          if (row[currentSelection] != null) {
            row[currentSelection] = 1;
          }
        });
      }
    },
    prepareCacheForTable() {
      let result = [];
      this.getSelectedRow(this.engines).forEach(() => {
        result = this.getSelectedRow(this.cache).concat(result)
      })
      return result
    },
    getSelectedRow(obj) {
      let result = [];
      for (let engine in obj) {
        for (let item in obj[engine]) {
          if (obj[engine][item]) {
            result.push(item);
          }
        }
      }
      return result;
    },
    getSelectedQueries(obj) {
      let result = [];
      for (let row in obj) {
        if (obj[row] === true) {
          result.push(row);
        }
      }
      return result;
    },
    parseEnginesGroups() {
      this.engineGroups = {};
      for (let engineFullName in this.supportedEngines) {

        let engineName = engineFullName.split('_');


        let selected = 0;

        if (this.engineGroups[engineName[0]] === undefined) {
          this.engineGroups[engineName[0]] = {};
        }

        for (let row of this.engines) {
          if (row[engineFullName] !== undefined) {
            selected = (row[engineFullName] !== 0 && row[engineFullName] !== false);
          }
        }
        this.engineGroups[engineName[0]][engineFullName] = selected;
      }
    }
  },
  computed: {
    getYear() {
      let today = new Date();
      return today.getFullYear();
    }
  }
};
String.prototype.capitalize = function () {
  return this.charAt(0).toUpperCase() + this.slice(1);
}
</script>

<style>
@import '~bootstrap/dist/css/bootstrap.css';

h4, .h4{
  font-weight: bold;
}
.caption {
  font-weight: 600;
  margin: 10px 20px;
}


.tooltip {
  display: block !important;
  z-index: 10000;
}


.tooltip {
  display: block !important;
  z-index: 10000;
}

.tooltip .tooltip-inner {
  background: black;
  color: white;
  border-radius: 16px;
  padding: 5px 10px 4px;
}

.tooltip .tooltip-arrow {
  width: 0;
  height: 0;
  border-style: solid;
  position: absolute;
  margin: 5px;
  border-color: black;
}

.tooltip[x-placement^="top"] {
  margin-bottom: 5px;
}

.tooltip[x-placement^="top"] .tooltip-arrow {
  border-width: 5px 5px 0 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  bottom: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="bottom"] {
  margin-top: 5px;
}

.tooltip[x-placement^="bottom"] .tooltip-arrow {
  border-width: 0 5px 5px 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-top-color: transparent !important;
  top: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="right"] {
  margin-left: 5px;
}

.tooltip[x-placement^="right"] .tooltip-arrow {
  border-width: 5px 5px 5px 0;
  border-left-color: transparent !important;
  border-top-color: transparent !important;
  border-bottom-color: transparent !important;
  left: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[x-placement^="left"] {
  margin-right: 5px;
}

.tooltip[x-placement^="left"] .tooltip-arrow {
  border-width: 5px 0 5px 5px;
  border-top-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  right: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[aria-hidden='true'] {
  visibility: hidden;
  opacity: 0;
  transition: opacity .15s, visibility .15s;
}

.tooltip[aria-hidden='false'] {
  visibility: visible;
  opacity: 1;
  transition: opacity .15s;
}

</style>
