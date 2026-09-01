<?php

namespace Tests\Feature;

use App\FilterRulesManager;
use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilterListControllerTest extends TestCase
{
    public function tearDown(): void
    {
        Storage::forgetDisk('local');
        Storage::forgetDisk(FilterImportGate::EXPORT_DISK);

        parent::tearDown();
    }

    public function testLegacyAdoptionRefusesADeniedCategoryThroughTheSharedGate()
    {
        Bus::fake();
        list($gate, $directory) = $this->makeGateFixture(2000, true, ['Uncategorized']);
        $this->bindGate($gate);

        try {
            $response = $this->withoutMiddleware()->get('/admin/update?file=export_uncategorized.zip');

            $response->assertStatus(403)
                ->assertSee('Import refused for category [uncategorized]')
                ->assertSee('The category is present in filter_imports.deny.');

            Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
        } finally {
            $this->removeGateFixture($directory);
        }
    }

    public function testLegacyAdoptionStillDownloadsOverHttpAndBypassesTheAllowlist()
    {
        Bus::fake();
        Storage::fake('local');
        config(['app.default_list_export_url' => 'https://manage.test/']);

        $body = fopen('php://temp', 'r+');
        fwrite($body, 'archive');
        rewind($body);

        $downloadResponse = new class($body)
        {
            private $body;

            public function __construct($body)
            {
                $this->body = $body;
            }

            public function getBody()
            {
                return $this->body;
            }
        };

        $client = new class($downloadResponse)
        {
            private $downloadResponse;
            public $requestedUrl;

            public function __construct($downloadResponse)
            {
                $this->downloadResponse = $downloadResponse;
            }

            public function get($url)
            {
                $this->requestedUrl = $url;

                return $this->downloadResponse;
            }
        };

        list($gate, $directory) = $this->makeGateFixture(2000, false);
        $this->bindGate($gate);

        $controller = new class($client) extends \App\Http\Controllers\FilterListController
        {
            private $client;

            public function __construct($client)
            {
                $this->client = $client;
            }

            protected function makeHttpClient()
            {
                return $this->client;
            }
        };

        try {
            $httpResponse = $controller->triggerUpdate(
                Request::create('/admin/update', 'GET', ['file' => 'export_new-category.zip'])
            );

            $this->assertSame(200, $httpResponse->getStatusCode());
            $this->assertSame('https://manage.test/export_new-category.zip', $client->requestedUrl);
            $this->assertContains('Downloading File from https://manage.test/export_new-category.zip', $httpResponse->getContent());
            $this->assertContains('Import has been triggered.', $httpResponse->getContent());

            Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, function ($job) {
                return $job->listNamespace === FilterImportGate::DEFAULT_NAMESPACE
                    && strpos($job->file, storage_path('app/export')) === 0
                    && $job->shouldOverwrite === true
                    && $job->category === 'new-category'
                    && $job->disk === null;
            });
        } finally {
            fclose($body);
            config(['app.default_list_export_url' => env('APP_DEFAULT_LIST_EXPORT_URL', '')]);
            $this->removeGateFixture($directory);
        }
    }

    /**
     * @param int|false|\Throwable $objectLastModified
     * @param bool $hasFilterLists
     * @param array $deniedCategories
     * @param int|null $ruleMtime
     * @return array
     */
    private function makeGateFixture(
        $objectLastModified,
        $hasFilterLists = true,
        $deniedCategories = [],
        $ruleMtime = null
    ) {
        $directory = sys_get_temp_dir() . '/filter-list-controller-' . bin2hex(random_bytes(8));
        mkdir($directory, 0755, true);

        $rulesManager = new class($directory) extends FilterRulesManager
        {
            private $directory;

            public function __construct($directory)
            {
                $this->directory = $directory;
            }

            public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.')
            {
                return $separatorChar . $listNamespace . $separatorChar . $listCategory . $separatorChar . $filename;
            }

            public function getRulesetPath($namespace, $category, $type)
            {
                return $this->directory . '/' . $type . '.txt';
            }
        };

        if ($ruleMtime !== null) {
            foreach (['rules', 'triggers'] as $type) {
                $path = $directory . '/' . $type . '.txt';
                file_put_contents($path, "rule\n");
                touch($path, $ruleMtime);
            }
        }

        $exportDisk = new class($objectLastModified)
        {
            private $objectLastModified;

            public function __construct($objectLastModified)
            {
                $this->objectLastModified = $objectLastModified;
            }

            public function exists($path)
            {
                if ($this->objectLastModified instanceof \Throwable) {
                    throw $this->objectLastModified;
                }

                return $this->objectLastModified !== false;
            }

            public function lastModified($path)
            {
                return $this->objectLastModified;
            }
        };

        return [
            new FilterImportGate(
                $exportDisk,
                $rulesManager,
                $deniedCategories,
                0,
                function ($namespace, $category) use ($hasFilterLists) {
                    return $hasFilterLists;
                }
            ),
            $directory,
        ];
    }

    private function removeGateFixture($directory)
    {
        foreach (glob($directory . '/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($directory);
    }

    private function bindGate($gate)
    {
        $this->app->bind(FilterImportGate::class, function () use ($gate) {
            return $gate;
        });
    }
}
