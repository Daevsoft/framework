<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Console\Commands\AddHelper;
use Ds\Console\Commands\AddJob;
use Ds\Console\Commands\CronJob as CronJobCommand;
use Ds\Foundations\Commands\CronJob\Job;
use PHPUnit\Framework\TestCase;

final class LegacyJobTest extends TestCase
{
    public function testLegacyHelperAndJobGeneratorsUsePsr4CasedPaths(): void
    {
        $jobDirectory = base_path('app/CronJobs/TestFixtures');
        $helperDirectory = base_path('app/Helpers/TestFixtures');
        if (!is_dir($jobDirectory)) {
            mkdir($jobDirectory, 0775, true);
        }
        if (!is_dir($helperDirectory)) {
            mkdir($helperDirectory, 0775, true);
        }
        $jobFile = $jobDirectory . '/GeneratedJobFixture.job.php';
        $helperFile = $helperDirectory . '/GeneratedHelperFixture.php';

        try {
            $jobCommand = new AddJob();
            $jobCommand->setParameters(['TestFixtures/GeneratedJobFixture']);
            self::assertSame(0, $jobCommand->handle());
            self::assertStringContainsString('namespace App\\CronJobs\\TestFixtures;', file_get_contents($jobFile));
            self::assertStringContainsString('extends Job', file_get_contents($jobFile));

            $helperCommand = new AddHelper();
            $helperCommand->setParameters(['TestFixtures/GeneratedHelperFixture']);
            self::assertSame(0, $helperCommand->handle());
            self::assertStringContainsString('namespace App\\Helpers\\TestFixtures;', file_get_contents($helperFile));
        } finally {
            @unlink($jobFile);
            @unlink($helperFile);
            @rmdir($jobDirectory);
            @rmdir($helperDirectory);
        }
    }

    public function testLegacyJobCanRunDirectlyOrThroughTheTaskWorkerContract(): void
    {
        $job = new class extends Job {
            public bool $started = false;

            protected function start(): void
            {
                $this->started = true;
            }
        };

        $job->run();
        self::assertTrue($job->started);

        $job->started = false;
        $job->handle();
        self::assertTrue($job->started);
    }

    public function testCronjobCommandExecutesNamespacedApplicationJob(): void
    {
        $directory = base_path('app/CronJobs/Compatibility');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = $directory . '/RunnerFixture.job.php';
        $source = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\CronJobs\Compatibility;

use Ds\Foundations\Commands\CronJob\Job;

final class RunnerFixture extends Job
{
    public static bool $ran = false;

    protected function start(): void
    {
        self::$ran = true;
    }
}
PHP;
        file_put_contents($file, $source);

        try {
            $command = new CronJobCommand();
            $command->setParameters(['Compatibility/RunnerFixture']);

            self::assertSame(0, $command->handle());
            self::assertTrue(\App\CronJobs\Compatibility\RunnerFixture::$ran);
        } finally {
            @unlink($file);
            @rmdir($directory);
            @rmdir(dirname($directory));
        }
    }
}
