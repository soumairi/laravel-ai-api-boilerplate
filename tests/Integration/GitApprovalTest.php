<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

function governanceProcess(array $command, string $directory, array $environment = []): Process
{
    $process = new Process($command, $directory, $environment);
    $process->setTimeout(30);

    return $process;
}

it('binds commit approval to the parent and staged content', function (): void {
    $directory = sys_get_temp_dir().'/laravel-api-governance-'.bin2hex(random_bytes(8));
    $traceId = 'AI-20260721-221204-068B439C';
    $traceDirectory = "$directory/ai/traces/2026/07";
    $traceFile = "$traceDirectory/$traceId.md";

    try {
        File::ensureDirectoryExists("$directory/scripts");
        File::copyDirectory(base_path('scripts/ai'), "$directory/scripts/ai");
        foreach (File::files("$directory/scripts/ai") as $script) {
            if ($script->getExtension() === 'sh') {
                chmod($script->getPathname(), 0755);
            }
        }
        File::ensureDirectoryExists($traceDirectory);
        File::put($traceFile, "# AI Trace\n\n- Trace ID: `$traceId`\n- Status: active\n");
        File::put("$directory/tracked.txt", "initial\n");

        foreach ([
            ['git', 'init'],
            ['git', 'config', 'user.email', 'governance@example.test'],
            ['git', 'config', 'user.name', 'Governance Test'],
            ['git', 'checkout', '-b', "ai/$traceId/fix/approval-test"],
            ['git', 'add', '.'],
            ['git', 'commit', '-m', 'test: initialize governance fixture'],
        ] as $command) {
            governanceProcess($command, $directory)->mustRun();
        }

        File::put("$directory/tracked.txt", "approved content\n");
        governanceProcess(['git', 'add', 'tracked.txt'], $directory)->mustRun();
        governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'record', 'commit', 'tester'],
            $directory,
            ['AI_HUMAN_APPROVAL' => 'yes'],
        )->mustRun();
        governanceProcess(['git', 'add', $traceFile], $directory)->mustRun();

        governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'commit'],
            $directory,
        )->mustRun();

        File::put("$directory/tracked.txt", "different content\n");
        governanceProcess(['git', 'add', 'tracked.txt'], $directory)->mustRun();
        $staleApproval = governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'commit'],
            $directory,
        );
        $staleApproval->run();

        expect($staleApproval->isSuccessful())->toBeFalse()
            ->and($staleApproval->getErrorOutput())->toContain('does not match the current content');

        governanceProcess(['git', 'add', '.'], $directory)->mustRun();
        governanceProcess(['git', 'commit', '-m', 'test: change approved content'], $directory)->mustRun();
        governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'record', 'push', 'tester'],
            $directory,
            ['AI_HUMAN_APPROVAL' => 'yes'],
        )->mustRun();
        governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'push'],
            $directory,
        )->mustRun();
        $wrongRemote = governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'push'],
            $directory,
            ['AI_APPROVAL_REMOTE' => 'mirror'],
        );
        $wrongRemote->run();

        expect($wrongRemote->isSuccessful())->toBeFalse()
            ->and($wrongRemote->getErrorOutput())->toContain('does not match the current content');

        governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'record', 'pr', 'tester'],
            $directory,
            ['AI_HUMAN_APPROVAL' => 'yes', 'AI_APPROVAL_BASE' => 'develop'],
        )->mustRun();
        $wrongBase = governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'pr'],
            $directory,
            ['AI_APPROVAL_BASE' => 'main'],
        );
        $wrongBase->run();

        expect($wrongBase->isSuccessful())->toBeFalse()
            ->and($wrongBase->getErrorOutput())->toContain('does not match the current content');

        File::put("$directory/tracked.txt", "new head\n");
        governanceProcess(['git', 'add', '.'], $directory)->mustRun();
        governanceProcess(['git', 'commit', '-m', 'test: advance head'], $directory)->mustRun();
        $stalePushApproval = governanceProcess(
            ['bash', 'scripts/ai/approval.sh', 'check', 'push'],
            $directory,
        );
        $stalePushApproval->run();

        expect($stalePushApproval->isSuccessful())->toBeFalse()
            ->and($stalePushApproval->getErrorOutput())->toContain('does not match the current content');
    } finally {
        File::deleteDirectory($directory);
    }
});
