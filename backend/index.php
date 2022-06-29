<?php

/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

class DataGetter
{
    const STATUS_SUCCESS = "success";
    const STATUS_ERROR = "error";

    private function printResponse($data, string $status = self::STATUS_SUCCESS, int $code = 200)
    {
        $result = ['status' => $status];
        if ($status === self::STATUS_ERROR) {
            if ($code === 200) {
                $code = 500;
            }
            $result['message'] = $data;
        } else {
            $result['result'] = $data;
        }


        try {
            $encodedResult = json_encode($result);
        } catch (Exception $e) {
            $code          = 500;
            $encodedResult = '{"status":"'.self::STATUS_ERROR.'", "message":"Error while json encoding"}';
        }

        http_response_code($code);
        echo $encodedResult;

        die();
    }

    private function request($query)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "http://db:9308/sql");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'query='.urlencode($query));

        $result   = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (empty($result)) {
            $this->printResponse("Server responsed with empty data", self::STATUS_ERROR);

            return false;
        }

        if ($httpCode != 200) {
            $this->printResponse("Response failed with error code ".$httpCode, self::STATUS_ERROR);

            return false;
        }

        return $result;
    }

    public function getRow($id)
    {
        $query = "SELECT * FROM results WHERE id = ".(int) $id;

        $decodedResult = json_decode($this->request($query), true);
        if ($decodedResult) {
            if ( ! isset($decodedResult['hits']['hits'][0]['_source'])) {
                $this->printResponse('Can\t parse results for requested row', self::STATUS_ERROR, 404);
            }

            $response = $this->parseSingleInfo($decodedResult['hits']['hits'][0]['_source']);
            unset($response['engine']);
            $this->printResponse($response);
        }

        $this->printResponse(['message' => 'Requested row not found'], self::STATUS_ERROR, 404);
    }

    public function getDiff($firstId, $secondId)
    {
        $query = "SELECT * FROM results WHERE id in (".(int) $firstId.",".(int) $secondId.")";

        $decodedResult = json_decode($this->request($query), true);
        if ($decodedResult && count($decodedResult['hits']['hits']) === 2) {
            $diff = [];
            foreach ($decodedResult['hits']['hits'] as $row) {
                $diff[] = $this->parseSingleInfo($row['_source']);
            }

            $this->printResponse($this->getSystemDiff($diff[0], $diff[1]));
        }

        $this->printResponse(['message' => 'Requested rows not found'], self::STATUS_ERROR, 404);
    }

    private function getSystemDiff($rowFirst, $rowSecond)
    {
        $rowFirstFileName  = DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$rowFirst['engine'];
        $rowSecondFileName = DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$rowSecond['engine'];
        unset($rowFirst['engine'], $rowSecond['engine']);
        $keys = array_keys($rowFirst);

        $compare = [];
        foreach ($keys as $key) {
            if (is_array($rowFirst[$key])) {
                $rowFirst[$key] = $this->formatArrayToText($rowFirst[$key]);
            }

            if (is_array($rowSecond[$key])) {
                $rowSecond[$key] = $this->formatArrayToText($rowSecond[$key]);
            }

            file_put_contents($rowFirstFileName, $rowFirst[$key]."\n");
            file_put_contents($rowSecondFileName, $rowSecond[$key]."\n");


            $command = 'diff -U 1000000 -u '.$rowFirstFileName.' '.$rowSecondFileName;
            $output  = [];
            exec($command, $output, $exitCode);
            if ($exitCode <= 1) {
                if ($output !== []) {
                    $compare[$key] = implode("\n", $output);
                } else {
                    $compare[$key]
                        = "diff --git $rowFirstFileName $rowFirstFileName\n--- $rowFirstFileName\n+++ $rowSecondFileName\nNo diff\n"
                        .$rowFirst[$key]."+".$rowFirst[$key];
                }
            } else {
                $this->printResponse(['message' => 'Error during diff generation'], self::STATUS_ERROR, 400);
            }
        }

        return $compare;
    }

    private function formatArrayToText($array)
    {
        $formatted = '';
        foreach ($array as $k => $v) {
            $formatted .= "$k: $v\n";
        }

        return $formatted;
    }

    private function parseSingleInfo(array $row)
    {
        return [
            'query'       => [
                'original_query' => $row['original_query'],
                'modified_query' => $row['modified_query'],
            ],
            'performance' => [
                'Cold run time'        => $row['cold'],
                'Fastest time'     => $row['fastest'],
                'Slowest time'     => $row['slowest'],
                'DB warmup time' => $row['warmup_time'],
                'Average time (all attempts)'         => $row['avg'],
                'CV (all attempts)'          => $row['cv'],

                'Avg(80% fastest)' => $row['avg_fastest'],

                'CV of avg(80% fastest)' => $row['cv_avg_fastest'],
                'Times count'    => count($row['times']),
            ],
            'response'    => $row['result'],
            'limits'      => [
                'memory' => $row['memory'],
            ],
            'engine'      => $row['engine_name'].'_'.$row['type'],
        ];
    }

    public function query()
    {
        $query
            = "SELECT ".
            "    id, test_name, memory, original_query, engine_name, type, avg(fastest), avg(slowest), ".
            "    avg(avg_fastest), min(checksum), max(query_timeout), group_concat(error), ".
            "    test_info, server_info ".
            "FROM results ".
            "WHERE error is NULL and query_timeout = 0 ".
            "GROUP BY test_name, engine_name, type, original_query, memory ".
            "ORDER BY original_query ASC ".
            "LIMIT 1000000 ".
            "OPTION max_matches=100000";

        // the below enables the mode which allows to visualize different attempts of the same engine + type, see also $engine = below
        //$query = "select test_name, test_time m, memory, original_query, engine_name, type, avg(fastest), avg(slowest), avg(avg_fastest), min(checksum), max(query_timeout), group_concat(error) from results where error is NULL and query_timeout = 0 group by test_name, test_time, engine_name, type, original_query, memory order by original_query asc limit 1000000";
        $result = $this->request($query);

        $result = $this->perpareResponse($result);
        if ( ! $result) {
            $this->printResponse("Error while preparing data", self::STATUS_ERROR);
        }

        $this->printResponse($result);

        return true;
    }

    private function perpareResponse($result)
    {
        $result = json_decode($result, true);
        if ( ! json_last_error()) {
            $data = [];

            $engines = [];
            $tests   = [];
            $memory  = [];

            $testsInfo       = [];
            $fullServerInfo  = [];
            $shortServerInfo = [];
            foreach ($result['hits']['hits'] as $row) {
                $id                       = $row['_id'];
                $row                      = $row['_source'];
                $tests[$row['test_name']] = 0;
                $memory[$row['memory']]   = 0;
                $engine                   = $row['engine_name'].($row['type'] ? '_'.$row['type'] : '');
                //$engine = $row['engine_name'].'_'.$row['m'].($row['type']?'_'.$row['type']:'');
                $engines[$engine] = 0;

                // converting from microseconds to milliseconds
                foreach (['avg(fastest)', 'avg(slowest)', 'avg(avg_fastest)'] as $v) {
                    $row[$v] = round($row[$v] / 1000);
                }

                // TODO: the timeout/error branch below is never executed, because we filter this out in the query above, but we need to be able to execute it and show error/timeout details in the UI
                if ($row['max(query_timeout)'] > 0 or $row['group_concat(error)']) {
                    $row['avg(fastest)'] = $row['avg(slowest)'] = $row['avg(avg_fastest)'] = -1;
                } else {
                    // let's round times lower than 1ms to 1ms
                    if ($row['avg(fastest)'] < 1) {
                        $row['avg(fastest)'] = 1;
                    }
                    if ($row['avg(slowest)'] < 1) {
                        $row['avg(slowest)'] = 1;
                    }
                    if ($row['avg(avg_fastest)'] < 1) {
                        $row['avg(avg_fastest)'] = 1;
                    }
                }


                $data[$row['test_name']][$row['memory']][md5($row['original_query'])]['query'] = $row['original_query'];
                $data[$row['test_name']][$row['memory']][md5($row['original_query'])][$engine] = [
                    'slowest'  => $row['avg(slowest)'],
                    'fastest'  => $row['avg(fastest)'],
                    'fast_avg' => $row['avg(avg_fastest)'],
                    'checksum' => $row['min(checksum)'],
                    'id'       => $id,
                ];

                $testsInfo[$row['test_name']] = $row['test_info'];

                if ( ! isset($fullServerInfo[$row['test_name']])) {
                    $fullServerInfo[$row['test_name']]  = $row['server_info'];
                    $shortServerInfo[$row['test_name']] = $this->parseShortServerInfo($row['server_info']);
                }
            }

            krsort($engines, SORT_STRING);

            for ($i = 1; $i <= 30; $i++) {
                $sorted   = [];
                $selected = [];
                foreach (['engines', 'tests', 'memory'] as $item) {
                    if ($item === 'memory') {
                        $key = $this->array_key_first($$item);
                    } elseif ($item === 'engines') {
                        $key = $this->array_random_keys($$item);
                    } else {
                        $key = $this->array_random_key($$item);
                    }

                    $selected[$item] = $key;


                    foreach ($$item as $name => $row) {
                        $sorted[$item][] = [$name => $row];
                    }
                }


                // Need to select available engine in this test

                if (isset($data[$selected['tests']][$selected['memory']])) {
                    $firstTestQuery = array_shift($data[$selected['tests']][$selected['memory']]);

                    if (isset($firstTestQuery[$selected['engines'][0]]) && isset($firstTestQuery[$selected['engines'][1]])) {
                        foreach (['engines', 'tests', 'memory'] as $item) {
                            foreach ($sorted[$item] as $k => $v) {
                                if ($item === 'engines') {
                                    if (in_array(key($v), $selected['engines'])) {
                                        $sorted[$item][$k][key($v)] = 1;
                                    }
                                } else {
                                    if (isset($v[$selected[$item]])) {
                                        $sorted[$item][$k][$selected[$item]] = 1;
                                    }
                                }
                            }
                        }

                        break;
                    }
                }
            }


            $data = [
                'data'            => $data,
                'tests'           => $sorted['tests'],
                'engines'         => $sorted['engines'],
                'memory'          => $sorted['memory'],
                'testsInfo'       => $testsInfo,
                'shortServerInfo' => $shortServerInfo,
                'fullServerInfo'  => $fullServerInfo,
            ];

            return $data;
        }

        return false;
    }


    private function array_key_first(array $array)
    {
        return array_keys($array)[0];
    }

    private function array_random_key(array $array)
    {
        $keys = array_keys($array);
        shuffle($keys);

        return $keys[0];
    }

    private function array_random_keys(array $array, int $count = 2): array
    {
        $keys = array_keys($array);
        shuffle($keys);

        $selectedKeys = [];
        for ($k = 0; $k < $count; $k++) {
            $selectedKeys[] = $keys[$k];
        }

        return $selectedKeys;
    }

    private function parseShortServerInfo($info)
    {
        $parsedInfo = json_decode($info, true);
        if (json_last_error() === 0) {
            if (isset($parsedInfo['cpuInfo'])) {
                if (preg_match("/model name\t: (.*?)\n/usi", $parsedInfo['cpuInfo'], $matches)) {
                    $cpuName = $matches[1];
                }

                $threads = substr_count($parsedInfo['cpuInfo'], 'processor');
            }

            $shortServerInfo = [];
            if ( ! empty($cpuName)) {
                $shortServerInfo[] = $cpuName;
            }

            if ( ! empty($threads)) {
                $shortServerInfo[] = $threads." threads in total";
            }


            return implode(', ', $shortServerInfo);
        }

        return "";
    }
}

$dg = new DataGetter();
if (isset($_GET['compare']) && isset($_GET['id1']) && isset($_GET['id2'])) {
    $dg->getDiff($_GET['id1'], $_GET['id2']);
} elseif ($_GET['info'] && isset($_GET['id'])) {
    $dg->getRow($_GET['id']);
} else {
    $dg->query();
}


