<?php

namespace Tests\Unit;

use App\Http\Controllers\ClientUserController;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NamesFromGoogleProfileTest extends TestCase
{
    #[DataProvider('googleProfileProvider')]
    public function test_names_from_google_profile(array $googleUser, array $expected): void
    {
        $controller = new ClientUserController;
        $method = new \ReflectionMethod(ClientUserController::class, 'namesFromGoogleProfile');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $googleUser);

        $this->assertSame($expected, $result);
    }

    public static function googleProfileProvider(): array
    {
        return [
            'single display name only' => [
                ['name' => 'Dilan', 'email' => 'test@example.com'],
                ['name' => 'Dilan', 'first_surname' => '-', 'second_surname' => null],
            ],
            'given and family name' => [
                ['given_name' => 'Dilan', 'family_name' => 'Pérez', 'name' => 'Dilan Pérez'],
                ['name' => 'Dilan', 'first_surname' => 'Pérez', 'second_surname' => null],
            ],
            'full name split' => [
                ['name' => 'Ana María López García'],
                ['name' => 'Ana', 'first_surname' => 'María', 'second_surname' => 'López García'],
            ],
        ];
    }
}
