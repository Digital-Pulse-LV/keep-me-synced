<?php

namespace DigitalPulse\KeepMeSynced\app\Http\Controllers;

use App\Http\Controllers\Controller;
use DigitalPulse\KeepMeSynced\app\Exceptions\KeepMeSyncedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use DigitalPulse\SlackLaravel\app\Services\SlackService;
use Illuminate\Http\JsonResponse;

class KeepMeSyncedController extends Controller
{
    /**
     * @throws KeepMeSyncedException
     */
    public function __construct()
    {
        if (empty(config('keep_me_synced.working_dir'))) {
            throw new KeepMeSyncedException('Error while running `composer update`: no working directory set.');
        }

        if (empty(config('keep_me_synced.composer_path'))) {
            throw new KeepMeSyncedException('Error while running `composer update`: no composer path set.');
        }
    }

    public function hook(Request $request): JsonResponse
    {
        $gitCommitMsg = $request->get('head_commit')['message'] ?? 'Unknown commit msg';

        SlackService::deploy('Updating', 'Updating application... `' . $gitCommitMsg . '`');

        $runGitPull = $this->runGitPull();
        if (!$runGitPull->isSuccessful()) {
            SlackService::error('Error while running git pull "' . $gitCommitMsg . '": `', $runGitPull->getErrorOutput() . '`');

            return new JsonResponse(['error' => true], 500);
        }

        $runComposer = $this->runComposer();
        if (!$runComposer->isSuccessful()) {
            SlackService::error('Error while running composer "' . $gitCommitMsg . '": `', $runComposer->getErrorOutput() . '`');

            return new JsonResponse(['error' => true], 500);
        }

        Artisan::call('optimize:clear');
        SlackService::deploy('Done', ':rocket: Application successfully updated: `' . $gitCommitMsg . '`');

        return new JsonResponse(['success' => true]);
    }

    private function runGitPull(): Process
    {
        $process = new Process(['git', 'pull']);
        $process->run();

        return $process;
    }

    private function runComposer(): Process
    {
        $process = new Process([config('keep_me_synced.composer_path'), 'update', '--no-dev', '--working-dir=' . config('keep_me_synced.working_dir')]);
        $process->run();

        return $process;
    }
}
