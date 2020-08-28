<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass NAVIT\AzureAd\Models\Group
 */
class GroupTest extends TestCase {
    /**
     * @return array<string, array<string>>
     */
    public function getCreationData() : array {
        return [
            'all elements present' => [
                'some-id',
                'some name',
                'some description',
                'mail'
            ],
        ];
    }

    /**
     * @covers ::fromArray
     * @covers ::getId
     * @covers ::getDisplayName
     * @covers ::getDescription
     * @covers ::getMailNickname
     * @covers ::__construct
     * @dataProvider getCreationData
     */
    public function testCanCreateFromArray(string $id, string $displayName, string $description, string $mailNickname) : void {
        $team = Group::fromArray([
            'id'           => $id,
            'displayName'  => $displayName,
            'description'  => $description,
            'mailNickname' => $mailNickname,
        ]);
        $this->assertSame($id, $team->getId());
        $this->assertSame($displayName, $team->getDisplayName());
        $this->assertSame($description, $team->getDescription());
        $this->assertSame($mailNickname, $team->getMailNickname());
    }

    /**
     * @return array<string, array{0: array{id?: string, displayName?: string, description?: string, mailNickname?: string}, 1: string}>
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
     * @param array{id?: string, displayName?: string, description?: string, mailNickname?: string} $data
     */
    public function testCanValidateInput(array $data, string $errorMessage) : void {
        $this->expectExceptionObject(new InvalidArgumentException($errorMessage));
        Group::fromArray($data);
    }
}