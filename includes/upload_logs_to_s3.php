<?php

require INCLUDES_PATH . '/aws.phar';

use Aws\S3\S3Client;

use Aws\Exception\AwsException;

function upload_logs_to_s3() {

	$sharedConfig = [
		'profile' => 'baseball',
		'region' => 'us-east-1',
		'version' => 'latest'
	];

	$sdk = new Aws\Sdk($sharedConfig);

	$s3Client = $sdk->createS3();

	$filename = date("Y-m-d") . "_" . time() . ".txt";

	$result = $s3Client->putObject([
		'Bucket' => 'tomgsmith99-baseball-logs',
		'Key' => $filename,
		'Body' => file_get_contents('/tmp/cron_debug_log.log')
	]);

	unlink('/tmp/cron_debug_log.log');
}
