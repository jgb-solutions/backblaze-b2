<?php

namespace JGBSolutions\Backblaze;

use Illuminate\Support\Facades\Cache;
use ChrisWhite\B2\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * {@inheritdoc}
     */
    protected function authorizeAccount()
    {
        $response = $this->auth();

        $this->authToken = $response['authorizationToken'];
        $this->apiUrl = $response['apiUrl'] . '/b2api/v1';
        $this->downloadUrl = $response['downloadUrl'];
    }

    /**
     * Get the latest authorization token.
     *
     * @return string
     */
    protected function token()
    {
        $request = $this->auth();

        return $request['authorizationToken'];
    }

    /**
     * Get an authorization token back from b2.
     *
     * @return array
     */
    protected function auth()
    {
        return Cache::remember('b2', 1320, function () {
            return $this->client->request('GET', 'https://api.backblaze.com/b2api/v1/b2_authorize_account', [
                'auth' => [$this->accountId, $this->applicationKey]
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function createBucket(array $options)
    {
        $this->authToken = $this->token();

        return parent::createBucket($options);
    }

    /**
     * {@inheritdoc}
     */
    public function updateBucket(array $options)
    {
        $this->authToken = $this->token();

        return parent::updateBucket($options);
    }

    /**
     * {@inheritdoc}
     */
    public function listBuckets()
    {
        $this->authToken = $this->token();

        return parent::listBuckets();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBucket(array $options)
    {
        $this->authToken = $this->token();

        return parent::deleteBucket($options);
    }

    /**
     * {@inheritdoc}
     */
    public function upload(array $options)
    {
        $this->authToken = $this->token();

        // Clean the path if it starts with /.
        if (substr($options['FileName'], 0, 1) === '/') {
            $options['FileName'] = ltrim($options['FileName'], '/');
        }

        if (!isset($options['BucketId']) && isset($options['BucketName'])) {
            $options['BucketId'] = $this->getBucketIdFromName($options['BucketName']);
        }

        // Retrieve the URL that we should be uploading to.
        $response = $this->client->request('POST', $this->apiUrl.'/b2_get_upload_url', [
            'headers' => [
                'Authorization' => $this->authToken,
            ],
            'json' => [
                'bucketId' => $options['BucketId']
            ]
        ]);

        $uploadEndpoint = $response['uploadUrl'];
        $uploadAuthToken = $response['authorizationToken'];

        if (is_resource($options['Body'])) {
            // We need to calculate the file's hash incrementally from the stream.
            $context = hash_init('sha1');
            hash_update_stream($context, $options['Body']);
            $hash = hash_final($context);

            // Similarly, we have to use fstat to get the size of the stream.
            $size = fstat($options['Body'])['size'];

            // Rewind the stream before passing it to the HTTP client.
            rewind($options['Body']);
        } else {
            // We've been given a simple string body, it's super simple to calculate the hash and size.
            $hash = sha1($options['Body']);
            $size = mb_strlen($options['Body']);
        }

        if (!isset($options['FileLastModified'])) {
            $options['FileLastModified'] = round(microtime(true) * 1000);
        }

        if (!isset($options['FileContentType'])) {
            $options['FileContentType'] = 'b2/x-auto';
        }

        $response = $this->client->request('POST', $uploadEndpoint, [
            'headers' => [
                'Authorization' => $uploadAuthToken,
                'Content-Type' => $options['FileContentType'],
                'Content-Length' => $size,
                'X-Bz-File-Name' => $options['FileName'],
                'X-Bz-Content-Sha1' => $hash,
                'X-Bz-Info-src_last_modified_millis' => $options['FileLastModified'],
                'X-Bz-Info-b2-content-disposition' => config('filesystems.b2', 'attachment')
            ],
            'body' => $options['Body']
        ]);

        return new File(
            $response['fileId'],
            $response['fileName'],
            $response['contentSha1'],
            $response['contentLength'],
            $response['contentType'],
            $response['fileInfo']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function download(array $options)
    {
        $this->authToken = $this->token();

        return parent::download($options);
    }

    /**
     * {@inheritdoc}
     */
    public function listFiles(array $options)
    {
        $this->authToken = $this->token();

        return parent::listFiles($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFile(array $options)
    {
        $this->authToken = $this->token();

        return parent::getFile($options);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile(array $options)
    {
        $this->authToken = $this->token();

        return parent::deleteFile($options);
    }
}
