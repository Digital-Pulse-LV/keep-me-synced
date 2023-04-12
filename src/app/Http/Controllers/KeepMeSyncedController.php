<?php

namespace DigitalPulse\KeepMeSynced\app\Http\Controllers;

use App\Http\Controllers\Controller;
use DigitalPulse\KeepMeSynced\app\Exceptions\KeepMeSyncedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use DigitalPulse\SlackLaravel\app\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Exception;

class KeepMeSyncedController extends Controller
{

    private bool $alreadyUpToDate = false;

    public function hook(Request $request): JsonResponse
    {
        $gitCommitMsg = $request->get('head_commit')['message'] ?? 'Unknown commit msg';

        SlackService::deploy('Updating', 'Updating application...');

        try {
            $this->runPull();
            $this->runComposer();
            Artisan::call('optimize:clear');
        } catch (Exception $e) {
            SlackService::error('Error while syncing "' . $gitCommitMsg . '": `', $e->getMessage() . '`');

            return new JsonResponse(['An error ocurred.'], 500);
        }

        SlackService::deploy('Done', ':rocket: Application successfully updated: `' . $gitCommitMsg . '`');

        return new JsonResponse(['Success']);
    }

    /**
     * @throws Exception
     */
    private function runPull(): void
    {
        $alreadyUpdated = false;
        $process = new Process(['git', 'pull']);

        $process->run(function ($type, $buffer) use ($alreadyUpdated) {
            if ($buffer == 'Already up to date.') {
                $alreadyUpdated = true;
            }
        });

        if (!$process->isSuccessful() && !$alreadyUpdated) {
            throw new KeepMeSyncedException('Error while running `git pull`: ' . $process->getErrorOutput());
        }
    }

    /**
     * @throws Exception
     * @throws KeepMeSyncedException
     */
    private function runComposer(): void
    {
        $alreadyUpdated = false;

        if (empty(config('keep_me_synced.working_dir'))) {
            throw new KeepMeSyncedException('Error while running `composer update`: no working directory set.');
        }

        if (empty(config('keep_me_synced.composer_path'))) {
            throw new KeepMeSyncedException('Error while running `composer update`: no composer path set.');
        }

        $process = new Process([config('keep_me_synced.composer_path'), 'update', '--no-dev', '--working-dir=' . config('keep_me_synced.working_dir')]);
        $process->run(function ($type, $buffer) use ($alreadyUpdated) {
            if ($buffer == 'Already up to date.') {
                $alreadyUpdated = true;
            }
        });

        if (!$process->isSuccessful() && !$alreadyUpdated) {
            throw new KeepMeSyncedException('Error while running `composer update`: ' . $process->getErrorOutput());
        }
    }
}
