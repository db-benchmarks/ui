<?php

/* Copyright (C) 2023 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/

header( 'Access-Control-Allow-Origin: *' );
header( 'Content-Type: application/json' );

class DataGetter {
	const STATUS_SUCCESS = "success";
	const STATUS_ERROR = "error";

	private $startTime;

	public function __construct( $startTime ) {
		$this->startTime = $startTime;
	}

	private function printResponse( $data, string $status = self::STATUS_SUCCESS, int $code = 200 ) {
		$result                  = [ 'status' => $status ];
		$result['executionTime'] = microtime( true ) - $this->startTime;

		if ( $status === self::STATUS_ERROR ) {
			if ( $code === 200 ) {
				$code = 500;
			}
			$result['message'] = $data;
		} else {
			$result['result'] = $data;
		}


		try {
			$encodedResult = json_encode( $result );
		} catch ( Exception $e ) {
			$code          = 500;
			$encodedResult = '{"status":"' . self::STATUS_ERROR . '", "message":"Error while json encoding"}';
		}

		http_response_code( $code );
		echo $encodedResult;

		die();
	}

	private function request( $query ) {
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, "http://db:9308/sql" );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'query=' . urlencode( $query ) );

		$result   = curl_exec( $curl );
		$httpCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		if ( empty( $result ) ) {
			$this->printResponse( "Server respond with empty data", self::STATUS_ERROR );

			return false;
		}

		if ( $httpCode != 200 ) {
			$this->printResponse( "Response failed with error code " . $httpCode, self::STATUS_ERROR );

			return false;
		}

		return $result;
	}

	public function getRow( int $id ) {
		$query = "SELECT * FROM results WHERE id = " . $id;

		$decodedResult = json_decode( $this->request( $query ), true );
		if ( $decodedResult ) {
			if ( ! isset( $decodedResult['hits']['hits'][0]['_source'] ) ) {
				$this->printResponse( 'Can\t parse results for requested row', self::STATUS_ERROR, 404 );
			}

			$response = $this->parseSingleInfo( $decodedResult['hits']['hits'][0]['_source'] );
			unset( $response['engine'] );
			$this->printResponse( $response );
		}

		$this->printResponse( [ 'message' => 'Requested row not found' ], self::STATUS_ERROR, 404 );
	}

	public function getDiff( int $firstId, int $secondId ) {
		$query = "SELECT * FROM results WHERE id in (" . $firstId . "," . $secondId . ")";

		$decodedResult = json_decode( $this->request( $query ), true );
		if ( $decodedResult && count( $decodedResult['hits']['hits'] ) === 2 ) {
			$diff = [];
			foreach ( $decodedResult['hits']['hits'] as $row ) {
				$diff[] = $this->parseSingleInfo( $row['_source'] );
			}

			$this->printResponse( $this->getSystemDiff( $diff[0], $diff[1] ) );
		}

		$this->printResponse( [ 'message' => 'Requested rows not found' ], self::STATUS_ERROR, 404 );
	}

	public function getDatasetInfo( int $id ) {
		$query = "SELECT * FROM results WHERE id = " . $id;

		$decodedResult = json_decode( $this->request( $query ), true );
		if ( $decodedResult ) {
			if ( ! isset( $decodedResult['hits']['hits'][0]['_source'] ) ) {
				$this->printResponse( 'Can\t parse results for requested row', self::STATUS_ERROR, 404 );
			}

			$info        = $decodedResult['hits']['hits'][0]['_source']['info'];
			$datasetInfo = [
				'DB Info' => [
					'Version'     => $info['version'],
					'URL'         => $info['url'],
					'Description' => $info['description'],
				],
				'Dataset' => [
					'Documents count' => $info['datasetCount'],
					'Sample document' => $info['datasetSampleDocument'],
				],
			];

			foreach ( [ 'version', 'url', 'description', 'datasetCount', 'datasetSampleDocument' ] as $key ) {
				unset( $info[ $key ] );
			}


			function walk( $item ) {
				if ( is_array( $item ) ) {
					foreach ( $item as $k => $v ) {
						$item[ $k ] = walk( $v );
					}

					return $item;
				}

				if ( mb_strlen( $item ) >= 24 ) {
					$words  = preg_split( '/(?<=[^\p{L}\p{N}])/u', $item, - 1, PREG_SPLIT_NO_EMPTY );
					$result = "";
					foreach ( $words as $word ) {
						if ( strlen( $word ) > 24 ) {
							$subwords = str_split( $word, 24 );
							$result   .= implode( " ", $subwords );
						} else {
							$result .= $word;
						}
					}
					$item = $result;
				}

				return $item;
			}


			$datasetInfo['Misc'] = array_map( 'walk', $info );


			$this->printResponse( $datasetInfo );
		}

		$this->printResponse( [ 'message' => 'Requested row not found' ], self::STATUS_ERROR, 404 );
	}

	private function getSystemDiff( $rowFirst, $rowSecond ) {
		$rowFirstFileName  = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $rowFirst['engine'] . '_' . $rowFirst['retest'];
		$rowSecondFileName = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $rowSecond['engine'] . '_' . $rowSecond['retest'];
		unset( $rowFirst['engine'], $rowSecond['engine'] );

		$compare = [];


		file_put_contents( $rowFirstFileName, $rowFirst['Response'] . "\n" );
		file_put_contents( $rowSecondFileName, $rowSecond['Response'] . "\n" );


		$command = 'diff -U 1000000 -u "' . $rowFirstFileName . '" "' . $rowSecondFileName . '"';
		$output  = [];
		exec( $command, $output, $exitCode );
		if ( $exitCode <= 1 ) {
			if ( $output !== [] ) {
				$compare['Response'] = implode( "\n",
					str_replace( DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR, "", $output ) );
			} else {
				$compare['Response'] = "--- " . $rowFirstFileName . "\n" .
				                       "+++ " . $rowSecondFileName . "\n" .
				                       "@@ -0 +0 @@\n" .
				                       $rowFirst['Response'];
			}
		} else {
			$this->printResponse( [ 'message' => 'Error during diff generation' ], self::STATUS_ERROR, 400 );
		}


		return $compare;
	}

	private function formatArrayToText( $array ) {
		$formatted = '';
		foreach ( $array as $k => $v ) {
			$formatted .= "$k: $v\n";
		}

		return $formatted;
	}

	private function parseSingleInfo( array $row ) {
		return [
			'Query'       => [
				'Original query' => $row['original_query'],
				'Adapted query'  => ' ' . $row['modified_query'],
			],
			'Performance' => [
				'Cold run time'               => number_format( $row['cold'] ) . " μs",
				'Fastest time'                => number_format( $row['fastest'] ) . " μs",
				'Slowest time'                => number_format( $row['slowest'] ) . " μs",
				'DB warmup time'              => number_format( $row['warmup_time'] ) . " μs",
				'Average time (all attempts)' => number_format( $row['avg'] ) . " μs",
				'CV (all attempts)'           => $row['cv'] . " %",

				'Avg (80% fastest)' => number_format( $row['avg_fastest'] ) . " μs",

				'CV of avg (80% fastest)' => $row['cv_avg_fastest'] . " %",
				'Query runs'              => count( $row['times'] ),
			],
			'Response'    => $row['result'],
			'Stats'       => $row['stats'],
			'Limits'      => [
				'RAM limit' => $row['memory'],
			],
			'engine'      => $row['engine_name'] . '_' . $row['type'],
			'retest'      => intval( $row['retest'] ),
		];
	}

	public function init(): bool {
		$query = "SELECT " .
		         "         id, test_name, memory, engine_name, type, retest " .
		         "     FROM results " .
		         "     WHERE error is NULL and query_timeout = 0 " .
			 "     GROUP BY test_name, memory, engine_name, type, retest " .
			 "     ORDER BY test_name ASC, engine_name ASC, type ASC, memory ASC " .
		         "     LIMIT 1000000 " .
		         "     OPTION max_matches=100000";

		// the below enables the mode which allows to visualize different attempts of the same engine + type, see also $engine = below
		//$query = "select test_name, test_time m, memory, original_query, engine_name, type, avg(fastest), avg(slowest), avg(avg_fastest), min(checksum), max(query_timeout), group_concat(error) from results where error is NULL and query_timeout = 0 group by test_name, test_time, engine_name, type, original_query, memory order by original_query asc limit 1000000";
		$result = $this->request( $query );

		$result = $this->prepareInitResponse( $result );
		if ( ! $result ) {
			$this->printResponse( "Error while preparing data", self::STATUS_ERROR );
		}

		$this->printResponse( $result );

		return true;
	}

	private function prepareInitResponse( $data ): bool|array {
		$data = json_decode( $data, true );
		if ( $data !== false ) {
			$results = [];
			if ( isset( $data['hits']['hits'] ) ) {
				foreach ( $data['hits']['hits'] as $row ) {
					$row                                              = $row['_source'];
					$results[ $row['test_name'] ][ $row['memory'] ][] = $row['engine_name'] . ( $row['type'] ? '_' . $row['type'] : '' )
					                                                    . ( $row['retest'] ? '_retest' : '' );
				}
			}

			if ( $results !== [] ) {
				return $results;
			}
		}

		return false;
	}

	public function getServerInfo( $testName ): bool {
		$query = "SELECT" .
		         "         id, test_name, test_info, server_info" .
		         "     FROM results " .
		         "WHERE error is NULL AND query_timeout = 0 " .
		         "    AND test_name = '" . $this->sanitizeTestName( $testName ) . "' " .
		         "LIMIT 1";

		$result = $this->request( $query );

		$result = $this->prepareInfoResponse( $result );
		if ( ! $result ) {
			$this->printResponse( "Error while preparing data", self::STATUS_ERROR );
		}

		$this->printResponse( $result );

		return true;
	}


    public function getInitInfo( $testName ): bool {
        $query /** @lang manticore */ = "select * from init_results " .
            "WHERE test_name = '" . $this->sanitizeTestName( $testName ) . "'";

        $result = $this->request( $query );

        $result = $this->prepareInitInfoResponse( $result );
        if ( ! $result ) {
            $this->printResponse( "Error while preparing data", self::STATUS_ERROR );
        }

        $this->printResponse( $result );

        return true;
    }

    private function prepareInitInfoResponse( $data ): bool|array {
        $data = json_decode( $data, true );
        if ( $data !== false ) {
            if ( isset( $data['hits']['hits'] ) ) {
                return array_map(function ($row){
                    return $row['_source'];
                }, $data['hits']['hits']);
            }
        }

        return false;
    }

	private function prepareInfoResponse( $data ): bool|array {
		$data = json_decode( $data, true );
		if ( $data !== false ) {
			if ( isset( $data['hits']['hits'] ) ) {
				foreach ( $data['hits']['hits'] as $row ) {
					$row = $row['_source'];

					return [
						'testsInfo'       => $row['test_info'],
						'shortServerInfo' => $this->parseShortServerInfo( $row['server_info'] ),
						'fullServerInfo'  => $row['server_info'],
					];
				}
			}
		}

		return false;
	}

	private function sanitizeTestName( $testName ) {
		$sanitizedTestName = preg_replace( '[^a-zA-Z0-9_]', '', $testName );
		if ( mb_strlen( $sanitizedTestName ) > 20 ) {
			$this->printResponse( "Test name can't be longer than 20 characters", self::STATUS_ERROR );
		}

		return $sanitizedTestName;
	}

	public function getTest( $testName, $memory ): bool {
		$query
			= "SELECT " .
			  "    id, test_name, memory, original_query, engine_name, type, avg(fastest), avg(slowest), " .
			  "    avg(avg_fastest), min(checksum), max(query_timeout), retest, group_concat(error) " .
			  "FROM results " .
			  "WHERE error is NULL AND query_timeout = 0 " .
			  "    AND test_name = '" . $this->sanitizeTestName( $testName ) . "'" .
			  "    AND memory = " . (int) $memory . " " .
			  "GROUP BY test_name, engine_name, type, original_query, memory, retest " .
			  "ORDER BY original_query ASC " .
			  "LIMIT 1000000 " .
			  "OPTION max_matches=100000";

		// the below enables the mode which allows to visualize different attempts of the same engine + type, see also $engine = below
		//$query = "select test_name, test_time m, memory, original_query, engine_name, type, avg(fastest), avg(slowest), avg(avg_fastest), min(checksum), max(query_timeout), group_concat(error) from results where error is NULL and query_timeout = 0 group by test_name, test_time, engine_name, type, original_query, memory order by original_query asc limit 1000000";
		$result = $this->request( $query );

		$result = $this->prepareTestResponse( $result );
		if ( ! $result ) {
			$this->printResponse( "Error while preparing data", self::STATUS_ERROR );
		}

		$this->printResponse( $result );

		return true;
	}

	private function prepareTestResponse( $result ) {
		$result = json_decode( $result, true );
		if ( ! json_last_error() ) {
			$data = [];

			$engines = [];
			$tests   = [];
			$memory  = [];

			$testsInfo = [];

			$queryId = null;
			foreach ( $result['hits']['hits'] as $row ) {
				if ( $queryId === null ) {
					$queryId = $row['_id'];
				}

				$id                         = $row['_id'];
				$row                        = $row['_source'];
				$tests[ $row['test_name'] ] = 0;
				$memory[ $row['memory'] ]   = 0;
				$engine                     = $row['engine_name'] . ( $row['type'] ? '_' . $row['type'] : '' );
				//$engine = $row['engine_name'].'_'.$row['m'].($row['type']?'_'.$row['type']:'');
				$engines[ $engine ] = 0;

				// converting from microseconds to milliseconds
				foreach ( [ 'avg(fastest)', 'avg(slowest)', 'avg(avg_fastest)' ] as $v ) {
					$row[ $v ] = round( $row[ $v ] / 1000 );
				}

				// TODO: the timeout/error branch below is never executed, because we filter this out in the query above, but we need to be able to execute it and show error/timeout details in the UI
				if ( $row['max(query_timeout)'] > 0 or $row['group_concat(error)'] ) {
					$row['avg(fastest)'] = $row['avg(slowest)'] = $row['avg(avg_fastest)'] = - 1;
				} else {
					// let's round times lower than 1ms to 1ms
					if ( $row['avg(fastest)'] < 1 ) {
						$row['avg(fastest)'] = 1;
					}
					if ( $row['avg(slowest)'] < 1 ) {
						$row['avg(slowest)'] = 1;
					}
					if ( $row['avg(avg_fastest)'] < 1 ) {
						$row['avg(avg_fastest)'] = 1;
					}
				}

				$data[ $row['test_name'] ][ $row['memory'] ][ md5( $row['original_query'] ) ]['query'] = $row['original_query'];

				if ( $row['retest'] ) {
					$data[ $row['test_name'] ][ $row['memory'] ][ md5( $row['original_query'] ) ][ $engine . "_retest" ] = [
						'slowest'  => $row['avg(slowest)'],
						'fastest'  => $row['avg(fastest)'],
						'fast_avg' => $row['avg(avg_fastest)'],
						'checksum' => $row['min(checksum)'],
						'id'       => $id,
					];
					continue;
				}

				$data[ $row['test_name'] ][ $row['memory'] ][ md5( $row['original_query'] ) ][ $engine ] = [
					'slowest'  => $row['avg(slowest)'],
					'fastest'  => $row['avg(fastest)'],
					'fast_avg' => $row['avg(avg_fastest)'],
					'checksum' => $row['min(checksum)'],
					'id'       => $id,
				];
			}

			ksort( $engines, SORT_STRING );

			$data = [
				'queryId'   => $queryId,
				'data'      => $data,
				'testsInfo' => $testsInfo,
			];

			return $data;
		}

		return false;
	}

	private function parseShortServerInfo( $info ) {
		$parsedInfo = json_decode( $info, true );
		if ( json_last_error() === 0 ) {
			if ( isset( $parsedInfo['cpuInfo'] ) ) {
				if ( preg_match( "/model name\t: (.*?)\n/usi", $parsedInfo['cpuInfo'], $matches ) ) {
					$cpuName = $matches[1];
				}

				$threads = substr_count( $parsedInfo['cpuInfo'], 'processor' );
			}

			$shortServerInfo = [];
			if ( ! empty( $cpuName ) ) {
				$shortServerInfo[] = $cpuName;
			}

			if ( ! empty( $threads ) ) {
				$shortServerInfo[] = $threads . " threads in total";
			}


			return implode( ', ', $shortServerInfo );
		}

		return "";
	}
}

$dg = new DataGetter( microtime( true ) );
if ( isset( $_GET['compare'] ) && isset( $_GET['id1'] ) && isset( $_GET['id2'] ) ) {
	$dg->getDiff( (int) $_GET['id1'], (int) $_GET['id2'] );
} elseif ( ! empty( $_GET['dataset_info'] ) && isset( $_GET['id'] ) ) {
	$dg->getDatasetInfo( (int) $_GET['id'] );
} elseif ( ! empty( $_GET['info'] ) && isset( $_GET['id'] ) ) {
	$dg->getRow( (int) $_GET['id'] );
} elseif ( ! empty( $_GET['test_name'] ) && ! empty( $_GET['memory'] ) ) {
	$dg->getTest( $_GET['test_name'], $_GET['memory'] );
} elseif ( ! empty( $_GET['server_info'] ) && ! empty( $_GET['test_name'] ) ) {
	$dg->getServerInfo( $_GET['test_name'] );
} elseif ( ! empty( $_GET['init_info'] ) && ! empty( $_GET['test_name'] ) ) {
    $dg->getInitInfo( $_GET['test_name'] );
} else {
	$dg->init();
}
