<?php
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Rubel\Models\Admin;
use Rubel\Models\Category;
use Rubel\Models\Tag;
use Rubel\Models\Config;
use Rubel\Models\Post;
use Rubel\Models\Comment;
use Rubel\Models\TagPost;
use Carbon\Carbon;

abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Run the default admin factory
     *
     * @return void
     */
    public function runDefaultAdmin(): void
    {
        $data = [
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        factory(Admin::class)->create($data);
    }

    /**
     * Get default headers with a jwt token for Api authentication
     *
     * @return array
     */
    public function getDefaultHeaders(): array
    {
        $credential = [
            'email' => 'admin@example.com',
            'password' => 'password',
        ];

        $response = $this->json('POST', route('api.authenticate.authenticate'), $credential);

        $jwtTokens = json_decode($response->getContent(), true)['token'];

        $headers = [
            "X-Requested-With" => "XMLHttpRequest",
            "Authorization" => $jwtTokens
        ];

        return $headers;
    }
}
