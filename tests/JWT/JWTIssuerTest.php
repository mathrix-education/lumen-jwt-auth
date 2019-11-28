<?php

declare(strict_types=1);

namespace Mathrix\Lumen\JWT\Auth\Tests\JWT;

use Illuminate\Support\Facades\Artisan;
use Jose\Component\Signature\JWS;
use Mathrix\Lumen\JWT\Auth\Commands\JWTKeyCommand;
use Mathrix\Lumen\JWT\Auth\HasJWT;
use Mathrix\Lumen\JWT\Auth\JWT\JWTIssuer;
use Mathrix\Lumen\JWT\Auth\Tests\SandboxTestCase;
use Mockery;
use Mockery\MockInterface;
use function count;
use function explode;
use function file_exists;
use function json_decode;

/**
 * @coversDefaultClass \Mathrix\Lumen\JWT\Auth\JWT\JWTIssuer
 */
class JWTIssuerTest extends SandboxTestCase
{
    /** @var JWTIssuer $jwtIssuer */
    private $jwtIssuer;

    public function setUp(): void
    {
        parent::setUp();
        $this->jwtIssuer = $this->app->make(JWTIssuer::class);

        if (file_exists($this->jwtIssuer->getJWKPath())) {
            return;
        }

        // If key does not exist
        Artisan::call(JWTKeyCommand::class);
    }

    /**
     * Get a mocked user.
     *
     * @return HasJWT
     */
    private function getUser()
    {
        /** @var HasJWT|MockInterface $user */
        $user = Mockery::mock(HasJWT::class);
        $user->shouldReceive('getKey')->withNoArgs()->andReturn(1);

        return $user;
    }

    /**
     * @param string $token A compact token.
     */
    private function assertValidCompactToken(string $token)
    {
        $this->assertStringStartsWith('ey', $token);
        $this->assertEquals(3, count(explode('.', $token)));
    }

    public function testIssueJWS(): JWS
    {
        $jwt     = $this->jwtIssuer->issueJWS($this->getUser(), ['cus1' => 'abc']);
        $payload = json_decode($jwt->getPayload());

        $this->assertInstanceOf(JWS::class, $jwt);
        $this->assertEquals('abc', $payload->cus1);

        return $jwt;
    }

    /**
     * @param JWS $jwt The previously issued JWS.
     *
     * @depends  testIssueJWS
     */
    public function testSerializeJWS(JWS $jwt): void
    {
        $token = $this->jwtIssuer->serializeJWS($jwt);
        $this->assertValidCompactToken($token);
    }

    public function testIssueJWSSerialized(): void
    {
        $token = $this->jwtIssuer->issueJWSSerialized($this->getUser());
        $this->assertValidCompactToken($token);
    }
}
