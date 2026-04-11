<?php

declare(strict_types=1);

namespace App\Command;

use App\Import\Status\ImportJobStore;
use App\Service\ImportArtifactStorage;
use App\Service\ImportManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:cleanup-import-jobs', description: 'Remove expired import jobs and managed artifacts.')]
final class CleanupImportJobsCommand extends Command
{
    public function __construct(
        private readonly ImportJobStore $importJobStore,
        private readonly ImportArtifactStorage $importArtifactStorage,
        private readonly ImportManager $importManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('older-than-hours', null, InputOption::VALUE_REQUIRED, 'Delete terminal jobs older than this many hours.', '168');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes without deleting jobs or files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = max(1, (int) $input->getOption('older-than-hours'));
        $dryRun = (bool) $input->getOption('dry-run');
        $threshold = new \DateTimeImmutable(sprintf('-%d hours', $hours), new \DateTimeZone('UTC'));
        $summary = $this->cleanupJobs($this->importJobStore->findExpiredTerminalJobs($threshold), $dryRun);

        $io->success($this->formatSummary($summary['jobs'], $summary['files'], $dryRun));

        return Command::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $jobs
     *
     * @return array{jobs: int, files: int}
     */
    private function cleanupJobs(array $jobs, bool $dryRun): array
    {
        $summary = ['jobs' => 0, 'files' => 0];

        foreach ($jobs as $job) {
            ++$summary['jobs'];
            $summary['files'] += $this->cleanupJobFiles($job, $dryRun);
            $this->deleteJob($job, $dryRun);
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function cleanupJobFiles(array $job, bool $dryRun): int
    {
        return $this->cleanupArtifact($job, $dryRun) + $this->cleanupStoredUpload($job, $dryRun);
    }

    /**
     * @param array<string, mixed> $job
     */
    private function cleanupArtifact(array $job, bool $dryRun): int
    {
        $artifactPath = is_string($job['artifact_file'] ?? null) ? $job['artifact_file'] : null;
        if (! $this->shouldDeleteArtifact($artifactPath)) {
            return 0;
        }

        if (! $dryRun) {
            $this->importArtifactStorage->deleteIfExists($artifactPath);
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function cleanupStoredUpload(array $job, bool $dryRun): int
    {
        $storedFile = is_string($job['file'] ?? null) ? $this->importManager->getFilePath($job['file']) : null;
        if (! $this->shouldDeleteStoredUpload($storedFile)) {
            return 0;
        }

        if (! $dryRun && is_file($storedFile)) {
            unlink($storedFile);
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function deleteJob(array $job, bool $dryRun): void
    {
        if (! $dryRun && is_string($job['id'] ?? null)) {
            $this->importJobStore->delete($job['id']);
        }
    }

    private function shouldDeleteArtifact(?string $artifactPath): bool
    {
        return $artifactPath !== null
            && $this->importArtifactStorage->isManagedPath($artifactPath)
            && is_file($artifactPath);
    }

    private function shouldDeleteStoredUpload(?string $storedFile): bool
    {
        return $storedFile !== null && is_file($storedFile);
    }

    private function formatSummary(int $jobCount, int $fileCount, bool $dryRun): string
    {
        return sprintf(
            '%s %d import jobs and %d related files.',
            $dryRun ? 'Would remove' : 'Removed',
            $jobCount,
            $fileCount,
        );
    }
}
