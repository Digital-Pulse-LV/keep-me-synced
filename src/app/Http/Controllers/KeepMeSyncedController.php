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
    private string $msg;

    /**
     * @throws KeepMeSyncedException
     */
    public function hook(Request $request): JsonResponse
    {
        try {
            $this->validateConfig();
            $this->setMsg($request);

            SlackService::deploy('Updating', 'Updating application `' . $this->msg . '`');

            $this->runGitPull();
            $this->runComposer();

            Artisan::call('optimize:clear');
        } catch (KeepMeSyncedException $e) {
            SlackService::error('Error while updating application', $this->msg . ': `' . $e->getMessage() . '`.');

            return new JsonResponse(['success' => false]);
        }

        SlackService::deploy('Done', ':rocket: Application successfully updated: `' . $this->msg . '`');

        return new JsonResponse(['success' => true]);
    }

    /**
     * @throws KeepMeSyncedException
     */
    private function validateConfig(): void
    {
        if (empty(config('keep_me_synced.working_dir'))) {
            throw new KeepMeSyncedException('No working directory set.');
        }

        if (empty(config('keep_me_synced.composer_path'))) {
            throw new KeepMeSyncedException('No composer path set.');
        }
    }

    /**
     * @throws KeepMeSyncedException
     */
    private function setMsg(Request $request)
    {
        $this->msg = $request->get('head_commit')['message'] ?? '';
        if (empty($this->msg)) {
            throw new KeepMeSyncedException('No commit message sent.');
        }
    }

    /**
     * @throws KeepMeSyncedException
     */
    private function runGitPull(): void
    {
        $process = new Process(['git', 'pull']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new KeepMeSyncedException($process->getErrorOutput());
        }
    }

    /**
     * @throws KeepMeSyncedException
     */
    private function runComposer(): void
    {
        $process = new Process([
            config('keep_me_synced.composer_path'),
            'update',
            '--no-dev',
            '--working-dir=' . config('keep_me_synced.working_dir'),
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new KeepMeSyncedException($process->getErrorOutput());
        }
    }
}
