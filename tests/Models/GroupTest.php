<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use NAVIT\AzureAd\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass NAVIT\AzureAd\Models\Group
 */
class GroupTest extends TestCase {
    public function getCreationData() : array {
        return [
            'all elements present' => [
                'some-id',
                'some name',
                'some description',
                'mail@nav.no'
            ],
        ];
    }

    /**
     * @covers ::fromArray
     * @covers ::getId
     * @covers ::getDisplayName
     * @covers ::getDescription
     * @covers ::getMail
     * @covers ::__construct
     * @dataProvider getCreationData
     */
    public function testCanCreateFromArray(string $id, string $displayName, string $description, string $mail) : void {
        $team = Group::fromArray([
            'id'          => $id,
            'displayName' => $displayName,
            'description' => $description,
            'mail'        => $mail,
        ]);
        $this->assertSame($id, $team->getId());
        $this->assertSame($displayName, $team->getDisplayName());
        $this->assertSame($description, $team->getDescription());
        $this->assertSame($mail, $team->getMail());
    }

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
     */
    public function testCanValidateInput(array $data, string $errorMessage) : void {
        $this->expectExceptionObject(new InvalidArgumentException($errorMessage));
        Group::fromArray($data);
    }
}