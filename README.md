<h1>Sync with Git Version Control for Laravel</h1>
<p>A simple package to keep repos in sync with cPanel Git Version Control.</p>

# Installation

<p>Simply run the following command to install:</p>

```sh
   composer require digital-pulse-lv/keep-me-synced
```

<p>Then publish the config file and adjust it as you need it:</p>

```sh
   php artisan vendor:publish --provider="DigitalPulse\KeepMeSynced\app\Providers\KeepMeSyncedServiceProvider"
```