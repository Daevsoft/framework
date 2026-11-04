<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Console\Commands\CronJob as CronJobCommand;
use Ds\Foundations\Commands\CronJob\Job;
use PHPUnit\Framework\TestCase;

final class LegacyJobTest extends TestCase
{
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
