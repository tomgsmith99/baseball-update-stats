<?php

// require INCLUDES_PATH . '/aws.phar';

require '/var/www/html/baseball_update_stats/aws.phar';

use Aws\S3\S3Client;

use Aws\Exception\AwsException;

// Use the us-east-2 region and latest version of each client.
$sharedConfig = [
    'profile' => 'baseball',
    'region' => 'us-east-1',
    'version' => 'latest'
];

// Create an SDK class used to share configuration across clients.
$sdk = new Aws\Sdk($sharedConfig);

// Use an Aws\Sdk class to create the S3Client object.
$s3Client = $sdk->createS3();

// Send a PutObject request and get the result object.
$result = $s3Client->putObject([
    'Bucket' => 'tomgsmith99-baseball-logs',
    'Key' => 'my-key2',
    'Body' => 'this is the body!'
]);

// Download the contents of the object.
$result = $s3Client->getObject([
    'Bucket' => 'tomgsmith99-baseball-logs',
    'Key' => 'my-key2'
]);

// Print the body of the result by indexing into the result object.
echo $result['Body'];