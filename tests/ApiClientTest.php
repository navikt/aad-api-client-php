<?php declare(strict_types=1);
namespace NAVIT\AzureAd;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * @coversDefaultClass NAVIT\AzureAd\ApiClient
 */
class ApiClientTest extends TestCase {
    private function getMockClient(array $responses, array &$history = []) : HttpClient {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));

        return new HttpClient(['handler' => $handler]);
    }

    /**
     * @covers ::__construct
     */
    public function testCanConstructClient() : void {
        $clientHistory = [];
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')],
            $clientHistory
        );

        new ApiClient($id = 'id', $secret = 'secret', $authClient);

        $this->assertCount(1, $clientHistory, 'Missing request');
        $request = $clientHistory[0]['request'];
        parse_str($request->getBody()->getContents(), $body);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($id, $body['client_id']);
        $this->assertSame($secret, $body['client_secret']);
    }

    /**
     * @covers ::getGroupById
     */
    public function testCanGetGroupById() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupById('some-id');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertInstanceOf(Models\Group::class, $aadGroup);
        $this->assertStringEndsWith('groups/some-id', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::getGroupById
     */
    public function testReturnsNullWhenGroupByIdRequestFails() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupById('some-id'));
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testCanGetGroupByDisplayName() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": [{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}]}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByDisplayName('some-display-name');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertInstanceOf(Models\Group::class, $aadGroup);
        $this->assertStringContainsString('filter=displayName%20eq%20%27some-display-name%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testReturnsNullWhenGroupByNameRequestFails() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByDisplayName('some display name'));
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testReturnsNullWhenGroupDoesNotExist() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": []}')],
            $clientHistory
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByDisplayName('some-display-name'));
        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertStringContainsString('filter=displayName%20eq%20%27some-display-name%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testCanGetGroupByMailNickname() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": [{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}]}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByMailNickname('mail');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertInstanceOf(Models\Group::class, $aadGroup);
        $this->assertStringContainsString('filter=mailNickname%20eq%20%27mail%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testReturnsNullWhenGroupByMailNicknameRequestFails() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByMailNickname('some mail nickname'));
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testReturnsNullWhenGroupWithNailNicknameDoesNotExist() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": []}')],
            $clientHistory
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupByMailNickname('some-mailnickname'));
        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertStringContainsString('filter=mailNickname%20eq%20%27some-mailnickname%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::createGroup
     */
    public function testCanCreateGroup() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(201, [], '{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', $authClient, $httpClient))->createGroup(
            'group name',
            'group description',
            ['Owner1@nav.no']
        );

        $this->assertInstanceOf(Models\Group::class, $aadGroup);
        $this->assertCount(1, $clientHistory, 'Expected one request');

        $request = $clientHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $body = json_decode($request->getBody()->getContents(), true);

        $this->assertSame('group name', $body['displayName'], 'Group name not correct');
        $this->assertStringStartsWith('group description', $body['description'], 'Group description not correct');
        $this->assertSame('group name', $body['mailNickname'], 'Mail not correct');
        $this->assertArrayHasKey('owners@odata.bind', $body, 'Missing owners list');
        $this->assertSame(['https://graph.microsoft.com/beta/users/Owner1@nav.no'], $body['owners@odata.bind'], 'Invalid owners list');
        $this->assertArrayNotHasKey('members@odata.bind', $body, 'Members list should not be present');
        $this->assertTrue($body['securityEnabled'], 'securityEnable flag not correct');
        $this->assertTrue($body['mailEnabled'], 'mailEnabled flag not correct');
    }

    /**
     * @covers ::addGroupToEnterpriseApp
     */
    public function testCanAddGroupToEnterpriseApp() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response()],
            $clientHistory
        );

        (new ApiClient('id', 'secret', $authClient, $httpClient))->addGroupToEnterpriseApp('group-id', 'app-object-id', 'app-role-id');

        $this->assertCount(1, $clientHistory, 'Expected one request');

        $request = $clientHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('servicePrincipals/app-object-id/appRoleAssignments', (string) $request->getUri());
        $this->assertSame([
            'principalId' => 'group-id',
            'appRoleId' => 'app-role-id',
            'resourceId' => 'app-object-id'
        ], json_decode($request->getBody()->getContents(), true), 'Incorrect request body');
    }

    /**
     * @covers ::getEnterpriseAppGroups
     * @covers ::getPaginatedData
     */
    public function testCanGetEnterpriseAppGroups() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [['principalId' => 'first-id', 'principalType' => 'Group']],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [['principalId' => 'second-id', 'principalType' => 'Group']],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [['principalId' => 'third-id', 'principalType' => 'User']],
                ])),
                new Response(200, [], (string) json_encode([
                    'id'           => 'first-id',
                    'displayName'  => 'first-group',
                    'description'  => 'first description',
                    'mailNickname' => 'first'
                ])),
                new Response(200, [], (string) json_encode([
                    'id'           => 'second-id',
                    'displayName'  => 'second-group',
                    'description'  => 'second description',
                    'mailNickname' => 'second',
                ])),
            ],
            $clientHistory
        );

        $groups = (new ApiClient('id', 'secret', $authClient, $httpClient))->getEnterpriseAppGroups('app-object-id');
        $this->assertCount(2, $groups);
        $this->assertCount(5, $clientHistory);
        $this->assertSame('servicePrincipals/app-object-id/appRoleAssignedTo?%24select=principalId%2CprincipalType&%24top=100', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
        $this->assertSame('next-link', (string) $clientHistory[2]['request']->getUri());
        $this->assertSame('groups/first-id', (string) $clientHistory[3]['request']->getUri());
        $this->assertSame('groups/second-id', (string) $clientHistory[4]['request']->getUri());
    }

    /**
     * @covers ::setGroupDescription
     */
    public function testCanSetGroupDescription() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(200)], $clientHistory);

        $this->assertTrue((new ApiClient('id', 'secret', $authClient, $httpClient))->setGroupDescription('group-id', 'description'));
        $this->assertCount(1, $clientHistory);
        $this->assertSame('groups/group-id', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('{"description":"description"}', (string) $clientHistory[0]['request']->getBody());
    }

    /**
     * @covers ::setGroupDescription
     */
    public function testSettingGroupDescriptionReturnsFalseOnFailure() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(404)], $clientHistory);

        $this->assertFalse((new ApiClient('id', 'secret', $authClient, $httpClient))->setGroupDescription('group-id', 'description'));
        $this->assertCount(1, $clientHistory);
        $this->assertSame('groups/group-id', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::getGroupMembers
     * @covers ::getPaginatedData
     */
    public function testCanGetGroupMembers() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [['id' => 'first-id', 'displayName' => 'Name 1', 'mail' => 'mail1@nav.no']],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [
                        ['id' => 'second-id', 'displayName' => 'Name 2', 'mail' => 'mail2@nav.no'],
                        ['id' => 'third-id', 'displayName' => 'Name 3'], // incomplete, will trigger error internally
                    ],
                ])),
            ],
            $clientHistory
        );

        $members = (new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupMembers('group-id');
        $this->assertCount(2, $members);
        $this->assertCount(2, $clientHistory);
        $this->assertSame('groups/group-id/members?%24select=id%2CdisplayName%2Cmail&%24top=100', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
    }

    /**
     * @covers ::getGroupOwners
     * @covers ::getPaginatedData
     */
    public function testCanGetGroupOwners() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [['id' => 'first-id', 'displayName' => 'Name 1', 'mail' => 'mail1@nav.no']],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [
                        ['id' => 'second-id', 'displayName' => 'Name 2', 'mail' => 'mail2@nav.no'],
                        ['id' => 'third-id', 'displayName' => 'Name 3'], // incomplete, will trigger error internally
                    ],
                ])),
            ],
            $clientHistory
        );

        $owners = (new ApiClient('id', 'secret', $authClient, $httpClient))->getGroupOwners('group-id');
        $this->assertCount(2, $owners);
        $this->assertCount(2, $clientHistory);
        $this->assertSame('groups/group-id/owners?%24select=id%2CdisplayName%2Cmail&%24top=100', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
    }

    /**
     * @covers ::getUserById
     */
    public function testCanGetUserById() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"id": "some-id", "displayName": "Bar, Foo", "mail": "foobar@nav.no"}')],
            $clientHistory
        );

        $aadUser = (new ApiClient('id', 'secret', $authClient, $httpClient))->getUserById('some-id');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertInstanceOf(Models\User::class, $aadUser);
        $this->assertStringEndsWith('users/some-id', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::getUserById
     */
    public function testReturnsNullWhenUserByIdRequestFails() : void {
        $authClient = $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')]
        );
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', $authClient, $httpClient))->getUserById('some-id'));
    }
}