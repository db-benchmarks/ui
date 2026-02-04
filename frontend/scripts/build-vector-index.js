#!/usr/bin/env node
/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */

const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const inputDir = path.join(rootDir, 'vector_results');
const outputDir = path.join(rootDir, 'public', 'vector-data');
const outputFile = path.join(outputDir, 'index.json');

function parseFilenameMeta(filename) {
  const base = path.basename(filename, '.json');
  const parts = base.split('-');
  if (parts.length < 8) {
    return { stage: null, searchIndex: null, lastTimestamp: null };
  }
  const timeParts = parts.slice(-6);
  if (!timeParts.every((part) => /^\d+$/.test(part))) {
    return { stage: null, searchIndex: null, lastTimestamp: null };
  }
  const remaining = parts.slice(0, -6);
  let stage = null;
  let searchIndex = null;
  if (remaining.length > 0) {
    const last = remaining[remaining.length - 1];
    const secondLast = remaining.length > 1 ? remaining[remaining.length - 2] : null;
    if (secondLast && /^\d+$/.test(last)) {
      searchIndex = Number(last);
      stage = secondLast;
    } else {
      stage = last;
    }
  }
  const [year, month, day, hour, minute, second] = timeParts.map(Number);
  const timestamp = new Date(Date.UTC(year, month - 1, day, hour, minute, second));
  const lastTimestamp = Number.isNaN(timestamp.getTime()) ? null : timestamp.toISOString();
  return { stage, searchIndex, lastTimestamp };
}

function getNumCandidates(params) {
  if (!params) {
    return null;
  }
  if (params.num_candidates !== undefined) {
    return params.num_candidates;
  }
  if (params.config) {
    if (params.config.num_candidates !== undefined) {
      return params.config.num_candidates;
    }
    if (params.config.ef !== undefined) {
      return params.config.ef;
    }
    if (params.config.hnsw_ef !== undefined) {
      return params.config.hnsw_ef;
    }
    if (params.config.EF !== undefined) {
      return params.config.EF;
    }
    if (params.config['knn.algo_param.ef_search'] !== undefined) {
      return params.config['knn.algo_param.ef_search'];
    }
  }
  if (params.search_params) {
    if (params.search_params.num_candidates !== undefined) {
      return params.search_params.num_candidates;
    }
    if (params.search_params.hnsw_ef !== undefined) {
      return params.search_params.hnsw_ef;
    }
  }
  return null;
}

function getConfig(params) {
  if (!params) {
    return null;
  }
  return params.config !== undefined ? params.config : null;
}

function getSearchParams(params) {
  if (!params) {
    return null;
  }
  return params.search_params !== undefined ? params.search_params : null;
}

function stableStringify(value) {
  if (value === null || value === undefined) {
    return '';
  }
  if (Array.isArray(value)) {
    return `[${value.map(stableStringify).join(',')}]`;
  }
  if (typeof value === 'object') {
    const keys = Object.keys(value).sort();
    return `{${keys.map((key) => `${key}:${stableStringify(value[key])}`).join(',')}}`;
  }
  return String(value);
}

function getOptions(params) {
  if (!params) {
    return null;
  }
  if (params.options !== undefined) {
    return params.options;
  }
  if (params.search_params && params.search_params.options !== undefined) {
    return params.search_params.options;
  }
  return null;
}

function getCollectionParams(params) {
  if (!params) {
    return null;
  }
  if (params.collection_params !== undefined) {
    return params.collection_params;
  }
  return null;
}

function getNormalizedVectorParams(params) {
  const collectionParams = getCollectionParams(params) || {};
  const config = getConfig(params) || {};
  const searchParams = getSearchParams(params) || {};

  const toNumberIfNumeric = (value) => {
    if (value === null || value === undefined) {
      return null;
    }
    if (typeof value === 'number') {
      return value;
    }
    if (typeof value === 'string' && value.trim() !== '') {
      const parsed = Number(value);
      if (!Number.isNaN(parsed)) {
        return parsed;
      }
    }
    return value;
  };

  const m = (
    (collectionParams.index_options && collectionParams.index_options.m) ??
    (collectionParams.method && collectionParams.method.parameters && collectionParams.method.parameters.m) ??
    (collectionParams.hnsw_config && collectionParams.hnsw_config.m) ??
    (collectionParams.vectorIndexConfig && collectionParams.vectorIndexConfig.maxConnections) ??
    null
  );

  const efBuild = (
    (collectionParams.index_options && collectionParams.index_options.ef_construction) ??
    (collectionParams.method && collectionParams.method.parameters && collectionParams.method.parameters.ef_construction) ??
    (collectionParams.hnsw_config && collectionParams.hnsw_config.ef_construct) ??
    (collectionParams.vectorIndexConfig && collectionParams.vectorIndexConfig.efConstruction) ??
    null
  );

  const efSearch = (
    config['knn.algo_param.ef_search'] ??
    config.num_candidates ??
    config.ef ??
    config.hnsw_ef ??
    config.EF ??
    searchParams.hnsw_ef ??
    searchParams.num_candidates ??
    null
  );

  return {
    m: toNumberIfNumeric(m),
    ef_build: toNumberIfNumeric(efBuild),
    ef_search: toNumberIfNumeric(efSearch)
  };
}

function collectJsonFiles(dirPath) {
  const entries = fs.readdirSync(dirPath, { withFileTypes: true });
  const files = [];
  for (const entry of entries) {
    const fullPath = path.join(dirPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectJsonFiles(fullPath));
    } else if (entry.isFile() && entry.name.endsWith('.json')) {
      files.push(fullPath);
    }
  }
  return files;
}

function compareTimestamps(a, b) {
  if (a && b) {
    return a > b ? 1 : (a < b ? -1 : 0);
  }
  if (a && !b) {
    return 1;
  }
  if (!a && b) {
    return -1;
  }
  return 0;
}

function inferStage(results, fallbackStage) {
  if (!results) {
    return fallbackStage || 'search';
  }
  if (fallbackStage === 'upload') {
    return 'upload';
  }
  if (results.upload_time !== undefined) {
    return 'upload';
  }
  if (results.post_upload !== undefined && (results.total_time !== undefined || results.upload_time !== undefined)) {
    return 'upload';
  }
  return fallbackStage || 'search';
}

function main() {
  if (!fs.existsSync(inputDir)) {
    console.error(`Missing input directory: ${inputDir}`);
    process.exit(1);
  }

  const files = collectJsonFiles(inputDir);
  if (files.length === 0) {
    console.error('No vector result files found in frontend/vector_results.');
    process.exit(1);
  }

  const aggregates = new Map();
  const datasets = new Set();
  const engines = new Set();

  for (const file of files) {
    let parsed;
    try {
      parsed = JSON.parse(fs.readFileSync(file, 'utf8'));
    } catch (error) {
      console.warn(`Skipping invalid JSON file: ${file}`);
      continue;
    }

    const params = parsed.params || {};
    const results = parsed.results || {};

    const dataset = params.dataset;
    const engine = params.engine;
    const experiment = params.experiment;

    if (!dataset || !engine || !experiment) {
      console.warn(`Skipping file with missing params: ${file}`);
      continue;
    }

    const { stage: stageFromName, searchIndex, lastTimestamp } = parseFilenameMeta(file);
    const stage = inferStage(results, stageFromName);
    const parallel = params.parallel !== undefined ? params.parallel : null;
    const numCandidates = getNumCandidates(params);
    const options = getOptions(params);
    const collectionParams = getCollectionParams(params);
    const config = getConfig(params);
    const searchParams = getSearchParams(params);
    const normalizedParams = getNormalizedVectorParams(params);

    if (stage === 'upload') {
      if (results.upload_time === undefined && results.total_time === undefined) {
        console.warn(`Skipping upload file with missing results: ${file}`);
        continue;
      }
    } else {
      if (results.rps === undefined || results.mean_precisions === undefined || results.p95_time === undefined || results.p99_time === undefined) {
        console.warn(`Skipping search file with missing results: ${file}`);
        continue;
      }
    }

    datasets.add(dataset);
    engines.add(engine);

    const keyParts = [
      dataset,
      engine,
      experiment,
      stage || '',
      searchIndex !== null && searchIndex !== undefined ? String(searchIndex) : '',
      parallel !== null && parallel !== undefined ? String(parallel) : '',
      numCandidates !== null && numCandidates !== undefined ? String(numCandidates) : '',
      stableStringify(options),
      stableStringify(collectionParams),
      stableStringify(config),
      stableStringify(searchParams),
      stableStringify(normalizedParams)
    ];
    const key = keyParts.join('|');

    const record = {
      dataset,
      engine,
      experiment,
      stage: stage || 'search',
      searchIndex: searchIndex !== null ? searchIndex : null,
      parallel: parallel !== null ? parallel : null,
      num_candidates: numCandidates !== null ? numCandidates : null,
      options: options !== null ? options : null,
      collection_params: collectionParams !== null ? collectionParams : null,
      config: config !== null ? config : null,
      search_params: searchParams !== null ? searchParams : null,
      normalized_params: normalizedParams,
      runs: 1,
      rps: stage === 'upload' ? null : results.rps,
      mean_precision: stage === 'upload' ? null : results.mean_precisions,
      p95_ms: stage === 'upload' ? null : results.p95_time * 1000,
      p99_ms: stage === 'upload' ? null : results.p99_time * 1000,
      mean_ms: stage === 'upload' ? null : (results.mean_time !== undefined ? results.mean_time * 1000 : null),
      upload_ms: stage === 'upload' ? (results.upload_time !== undefined ? results.upload_time * 1000 : null) : null,
      total_upload_ms: stage === 'upload' ? (results.total_time !== undefined ? results.total_time * 1000 : null) : null,
      last_timestamp: lastTimestamp
    };

    if (!aggregates.has(key)) {
      aggregates.set(key, record);
    } else {
      const existing = aggregates.get(key);
      const cmp = compareTimestamps(record.last_timestamp, existing.last_timestamp);
      if (cmp > 0) {
        aggregates.set(key, record);
      }
    }
  }

  const records = Array.from(aggregates.values());

  records.sort((a, b) => {
    const keys = ['dataset', 'engine', 'experiment', 'stage'];
    for (const key of keys) {
      if (a[key] !== b[key]) {
        return String(a[key]).localeCompare(String(b[key]));
      }
    }
    const numericKeys = ['searchIndex', 'parallel', 'num_candidates'];
    for (const key of numericKeys) {
      const aVal = a[key] === null ? -1 : a[key];
      const bVal = b[key] === null ? -1 : b[key];
      if (aVal !== bVal) {
        return aVal - bVal;
      }
    }
    return 0;
  });

  const searchRecords = records.filter((record) => record.stage === 'search');
  const uploadRecords = records.filter((record) => record.stage === 'upload');

  const datasetEngines = new Map();
  for (const record of records) {
    if (!record.dataset || !record.engine) {
      continue;
    }
    if (!datasetEngines.has(record.dataset)) {
      datasetEngines.set(record.dataset, new Set());
    }
    datasetEngines.get(record.dataset).add(record.engine);
  }

  const output = {
    generated_at: new Date().toISOString(),
    datasets: Array.from(datasets).sort(),
    engines: Array.from(engines).sort(),
    dataset_engines: Object.fromEntries(
      Array.from(datasetEngines.entries())
        .sort(([a], [b]) => String(a).localeCompare(String(b)))
        .map(([dataset, engineSet]) => [dataset, Array.from(engineSet).sort()])
    ),
    search_records: searchRecords,
    upload_records: uploadRecords
  };

  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(outputFile, JSON.stringify(output, null, 2));
  console.log(`Vector index written to ${outputFile}`);
}

main();
