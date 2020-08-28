<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass NAVIT\AzureAd\Models\User
 */
class UserTest extends TestCase {
    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: bool}>
     */
    public function getCreationData() : array {
        return [
            'all elements present' => [
                'some-id',
                'some name',
                'mail@nav.no',
                true,
            ],
        ];
    }

    /**
     * @covers ::fromArray
     * @covers ::getId
     * @covers ::getDisplayName
     * @covers ::getMail
     * @covers ::getAccountEnabled
     * @covers ::__construct
     * @dataProvider getCreationData
     */
    public function testCanCreateFromArray(string $id, string $displayName, string $mail, bool $accountEnabled) : void {
        $user = User::fromArray([
            'id'             => $id,
            'displayName'    => $displayName,
            'mail'           => $mail,
            'accountEnabled' => $accountEnabled,
        ]);
        $this->assertSame($id, $user->getId());
        $this->assertSame($displayName, $user->getDisplayName());
        $this->assertSame($mail, $user->getMail());
        $this->assertSame($accountEnabled, $user->getAccountEnabled());
    }

    /**
     * @return array<string, array{0: array{id?: string, displayName?: string, mail?: string, accountEnabled?: bool}, 1: string}>
     */
    public function getInvalidData() : array {
        return [
            'missing id' => [
                [
                    'displayName' => 'name',
                ],
                'Missing data element: id'
            ],
            'missing display name' => [
                [
                    'id' => 'some-id',
                ],
                'Missing data element: displayName'
            ],
        ];
    }

    /**
     * @covers ::fromArray
     * @dataProvider getInvalidData
     * @param array{id?: string, displayName?: string, mail?: string, accountEnabled?: bool} $data
     */
    public function testCanValidateInput(array $data, string $errorMessage) : void {
        $this->expectExceptionObject(new InvalidArgumentException($errorMessage));
        User::fromArray($data);
    }
}