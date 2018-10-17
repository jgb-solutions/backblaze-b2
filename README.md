# Backblaze Laravel Adapter

Setup:

```
composer require jgb-solutions/backblaze-b2
```

If on Laravel 5.5+ the package will auto register itself. Otherwise register `JGBSolutions\Backblaze\BackblazeServiceProvider::class`, then add a config array in `filesystems.php`.

```
'b2' => [
    'driver' => 'b2',
    'key' => env('BACKBLAZE_B2_KEY'),
    'host' => env('BACKBLAZE_B2_HOST'),
    'bucket' => env('BACKBLAZE_B2_BUCKET'),
    'account' => env('BACKBLAZE_B2_ACCOUNT'),
    'disposition' => env('BACKBLAZE_B2_DISPOSITION')
],
```

`host` can be set if you want to link directly to files in buckets marked `allPublic`.

See [this handy guide](https://silversuit.net/blog/2016/04/how-to-set-up-a-practically-free-cdn/) for setting up cloudflare page rules to turn your bucket into a CDN.

## Features

- Caches the auth token, meaning you don't constantly hit the auth endpoint.
- Refreshes the auth token for long-running processes (like `queue:work`).
- Option to specifiy the `Content-Disposition` header using the `X-Bz-Info-b2-content-disposition` header. Default is `attachment`.