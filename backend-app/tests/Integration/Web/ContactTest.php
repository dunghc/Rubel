<?php
namespace Tests\Integration\Web;

use TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * STATUS_CODE_OK
     *
     * @var int
     */
    const STATUS_CODE_OK = 200;

    public function testIndex()
    {
        $response = $this->get(route('web.root.index'));

        $response->assertStatus(self::STATUS_CODE_OK);
    }
}
