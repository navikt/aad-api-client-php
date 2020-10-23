<?php declare(strict_types=1);
namespace NAVIT\AzureAd;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\{
    Client as HttpClient,
    Handler\MockHandler,
    HandlerStack,
    Psr7\Response,
    Psr7\Request,
    Middleware,
};
use RuntimeException;

/**
 * @coversDefaultClass NAVIT\AzureAd\ApiClient
 */
class ApiClientTest extends TestCase {
    /**
     * @param array<int,Response> $responses
     * @param array<int,array{response:Response,request:Request}> $history
     * @param-out array<int,array{response:Response,request:Request}> $history
     * @return HttpClient
     */
    private function getMockClient(array $responses, array &$history = []) : HttpClient {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));

        return new HttpClient(['handler' => $handler]);
    }

    /**
     * @param array<int,array{response:Response,request:Request}> $history $history
     * @return HttpClient
     */
    private function getMockedAuthClient(array &$history = []) : HttpClient {
        return $this->getMockClient(
            [new Response(200, [], '{"access_token": "some secret token"}')],
            $history
        );
    }

    /**
     * @covers ::__construct
     */
    public function testCanConstructClient() : void {
        $clientHistory = [];
        $authClient = $this->getMockedAuthClient($clientHistory);

        new ApiClient($id = 'id', $secret = 'secret', 'nav.no', $authClient);

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
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupById('some-id');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertSame([
            'id'           => 'some-id',
            'displayName'  => 'some-display-name',
            'description'  => 'some description',
            'mailNickname' => 'mail',
        ], $aadGroup);
        $this->assertStringEndsWith('groups/some-id', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::getGroupById
     */
    public function testReturnsNullWhenGroupByIdRequestFails() : void {
        $this->assertNull(
            (new ApiClient(
                'id',
                'secret',
                'nav.no',
                $this->getMockedAuthClient(),
                $this->getMockClient([new Response(404)])
            ))->getGroupById('some-id')
        );
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testCanGetGroupByDisplayName() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": [{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}]}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupByDisplayName('some-display-name');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertSame([
            'id'           => 'some-id',
            'displayName'  => 'some-display-name',
            'description'  => 'some description',
            'mailNickname' => 'mail',
        ], $aadGroup);
        $this->assertStringContainsString('filter=displayName%20eq%20%27some-display-name%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testReturnsNullWhenGroupByNameRequestFails() : void {
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupByDisplayName('some display name'));
    }

    /**
     * @covers ::getGroupByDisplayName
     */
    public function testReturnsNullWhenGroupDoesNotExist() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": []}')],
            $clientHistory
        );

        $this->assertNull((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupByDisplayName('some-display-name'));
        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertStringContainsString('filter=displayName%20eq%20%27some-display-name%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testCanGetGroupByMailNickname() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": [{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}]}')],
            $clientHistory
        );

        $aadGroup = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupByMailNickname('mail');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertSame([
            'id'           => 'some-id',
            'displayName'  => 'some-display-name',
            'description'  => 'some description',
            'mailNickname' => 'mail',
        ], $aadGroup);
        $this->assertStringContainsString('filter=mailNickname%20eq%20%27mail%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testReturnsNullWhenGroupByMailNicknameRequestFails() : void {
        $this->assertNull(
            (new ApiClient(
                'id',
                'secret',
                'nav.no',
                $this->getMockedAuthClient(),
                $this->getMockClient([new Response(404)])
            ))->getGroupByMailNickname('some mail nickname')
        );
    }

    /**
     * @covers ::getGroupByMailNickname
     */
    public function testReturnsNullWhenGroupWithNailNicknameDoesNotExist() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"value": []}')],
            $clientHistory
        );

        $this->assertNull((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupByMailNickname('some-mailnickname'));
        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertStringContainsString('filter=mailNickname%20eq%20%27some-mailnickname%27', $clientHistory[0]['request']->getUri()->getQuery());
    }

    /**
     * @covers ::createGroup
     */
    public function testCanCreateGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(201, [], '{"id": "some-id", "displayName": "some-display-name", "description": "some description", "mailNickname": "mail"}')],
            $clientHistory
        );

        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->createGroup(
            'group name',
            'group description',
            ['Owner1@nav.no']
        );

        $this->assertCount(1, $clientHistory, 'Expected one request');

        $request = $clientHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        /**
         * @var array{
         *     displayName: string,
         *     description: string,
         *     mailNickname: string,
         *     "owners@odata.bind": array,
         *     securityEnabled: bool,
         *     mailEnabled: bool
         * }
         */
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
     * @covers ::createGroup
     */
    public function testThrowsExceptionWhenFailingToCreateGroup() : void {
        $httpClient = $this->getMockClient([new Response(400)]);

        $this->expectExceptionObject(new RuntimeException('Unable to create group', 400));
        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->createGroup(
            'group name',
            'group description',
            ['Owner1@nav.no']
        );
    }

    /**
     * @covers ::addGroupToEnterpriseApp
     */
    public function testCanAddGroupToEnterpriseApp() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response()],
            $clientHistory
        );

        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->addGroupToEnterpriseApp('group-id', 'app-object-id', 'app-role-id');
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
     * @covers ::addGroupToEnterpriseApp
     */
    public function testThrowsExceptionWhenFailingToAddGroupToEnterpriseApplication() : void {
        $this->expectExceptionObject(new RuntimeException('Unable to add group to enterprise application', 400));
        (new ApiClient(
            'id',
            'secret',
            'nav.no',
            $this->getMockedAuthClient(),
            $this->getMockClient([new Response(400)])
        ))->addGroupToEnterpriseApp('group-id', 'app-object-id', 'app-role-id');
    }

    /**
     * @covers ::getEnterpriseAppGroups
     * @covers ::getPaginatedData
     */
    public function testCanGetEnterpriseAppGroups() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link?foo=bar&token=123',
                    'value' => [['principalId' => 'first-id', 'principalType' => 'Group']],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link?foo=bar&token=456',
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

        $groups = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getEnterpriseAppGroups('app-object-id');
        $this->assertCount(2, $groups);
        $this->assertCount(5, $clientHistory);
        $this->assertSame('servicePrincipals/app-object-id/appRoleAssignedTo?%24select=principalId%2CprincipalType&%24top=100', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('next-link?foo=bar&token=123', (string) $clientHistory[1]['request']->getUri());
        $this->assertSame('next-link?foo=bar&token=456', (string) $clientHistory[2]['request']->getUri());
        $this->assertSame('groups/first-id', (string) $clientHistory[3]['request']->getUri());
        $this->assertSame('groups/second-id', (string) $clientHistory[4]['request']->getUri());
    }

    /**
     * @covers ::setGroupDescription
     */
    public function testCanSetGroupDescription() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(200)], $clientHistory);

        $this->assertTrue((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->setGroupDescription('group-id', 'description'));
        $this->assertCount(1, $clientHistory);
        $this->assertSame('groups/group-id', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('{"description":"description"}', (string) $clientHistory[0]['request']->getBody());
    }

    /**
     * @covers ::setGroupDescription
     */
    public function testSettingGroupDescriptionReturnsFalseOnFailure() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(404)], $clientHistory);

        $this->assertFalse((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->setGroupDescription('group-id', 'description'));
        $this->assertCount(1, $clientHistory);
        $this->assertSame('groups/group-id', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::getGroupMembers
     * @covers ::getPaginatedData
     */
    public function testCanGetGroupMembers() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [
                        [
                            'id'             => 'first-id',
                            'displayName'    => 'Name 1',
                            'mail'           => 'mail1@nav.no',
                            'accountEnabled' => false,
                        ],
                    ],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [
                        [
                            'id'             => 'second-id',
                            'displayName'    => 'Name 2',
                            'mail'           => 'mail2@nav.no',
                            'accountEnabled' => true,
                        ],
                        [
                            'id'          => 'third-id',
                            'displayName' => 'Name 3',
                        ],
                    ],
                ])),
            ],
            $clientHistory
        );

        $members = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupMembers('group-id');
        $this->assertCount(3, $members);
        $this->assertCount(2, $clientHistory);
        $this->assertSame('groups/group-id/members', $clientHistory[0]['request']->getUri()->getPath());
        $this->assertSame('%24select=id%2CdisplayName%2Cmail%2CaccountEnabled&%24top=100', $clientHistory[0]['request']->getUri()->getQuery());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
    }

    /**
     * @covers ::getGroupOwners
     * @covers ::getPaginatedData
     */
    public function testCanGetGroupOwners() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [
                        [
                            'id'             => 'first-id',
                            'displayName'    => 'Name 1',
                            'mail'           => 'mail1@nav.no',
                            'accountEnabled' => true,
                        ],
                    ],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [
                        [
                            'id'             => 'second-id',
                            'displayName'    => 'Name 2',
                            'mail'           => 'mail2@nav.no',
                            'accountEnabled' => true,
                        ],
                        [
                            'id'          => 'third-id',
                            'displayName' => 'Name 3',
                        ],
                    ],
                ])),
            ],
            $clientHistory,
        );

        $owners = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getGroupOwners('group-id');
        $this->assertCount(3, $owners);
        $this->assertCount(2, $clientHistory);
        $this->assertSame('groups/group-id/owners', $clientHistory[0]['request']->getUri()->getPath());
        $this->assertSame('%24select=id%2CdisplayName%2Cmail%2CaccountEnabled&%24top=100', $clientHistory[0]['request']->getUri()->getQuery());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
    }

    /**
     * @covers ::getUserById
     */
    public function testCanGetUserById() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [new Response(200, [], '{"id": "some-id", "displayName": "Bar, Foo", "mail": "foobar@nav.no", "accountEnabled": false}')],
            $clientHistory
        );

        $aadUser = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getUserById('some-id');

        $this->assertCount(1, $clientHistory, 'Expected one request');
        $this->assertSame([
            'id'             => 'some-id',
            'displayName'    => 'Bar, Foo',
            'mail'           => 'foobar@nav.no',
            'accountEnabled' => false,
        ], $aadUser);

        $uri = $clientHistory[0]['request']->getUri();

        $this->assertSame('users/some-id', $uri->getPath());
        $this->assertSame('%24select=id%2CdisplayName%2Cmail%2CaccountEnabled', $uri->getQuery());

    }

    /**
     * @covers ::getUserById
     */
    public function testReturnsNullWhenUserByIdRequestFails() : void {
        $httpClient = $this->getMockClient(
            [new Response(404)]
        );

        $this->assertNull((new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getUserById('some-id'));
    }

    /**
     * @covers ::getUserGroups
     * @covers ::getPaginatedData
     */
    public function testCanGetUserGroups() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient(
            [
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    '@odata.nextLink' => 'next-link',
                    'value' => [
                        [
                            'id'           => 'id1',
                            'displayName'  => 'name1',
                            'description'  => 'desc1',
                            'mailNickname' => 'mail1',
                        ],
                    ],
                ])),
                new Response(200, [], (string) json_encode([
                    '@odata.context' => 'context-url',
                    'value' => [
                        [
                            'id' => 'id2',
                            'displayName'  => 'name2',
                            'description'  => 'desc2',
                            'mailNickname' => 'mail2',
                        ],
                        [
                            'id'           => 'id3',
                            'displayName'  => 'name3',
                            'description'  => 'desc3',
                        ],
                    ],
                ])),
            ],
            $clientHistory
        );

        $groups = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->getUserGroups('user-id');
        $this->assertCount(3, $groups);
        $this->assertCount(2, $clientHistory);
        $this->assertSame('users/user-id/memberOf/microsoft.graph.group?%24select=id%2CdisplayName%2Cdescription%2CmailNickname&%24top=100', (string) $clientHistory[0]['request']->getUri());
        $this->assertSame('next-link', (string) $clientHistory[1]['request']->getUri());
    }

    /**
     * @covers ::getPaginatedData
     */
    public function testThrowsExceptionWhenUnableToFetchPaginatedData() : void {
        $this->expectExceptionObject(new RuntimeException('Unable to fetch paginated data', 401));
        (new ApiClient(
            'id',
            'secret',
            'nav.no',
            $this->getMockedAuthClient(),
            $this->getMockClient([new Response(401)])
        ))->getUserGroups('user-id');
    }

    /**
     * @covers ::addUserToGroup
     */
    public function testCanAddUserToGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(204)], $clientHistory);

        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->addUserToGroup('user-id', 'group-id');
        $this->assertCount(1, $clientHistory);
        $request = $clientHistory[0]['request'];
        $this->assertSame('groups/group-id/members/$ref', (string) $request->getUri());
        $this->assertSame(['@odata.id' => 'https://graph.microsoft.com/beta/users/user-id'], json_decode($request->getBody()->getContents(), true));
    }

    /**
     * @covers ::addUserToGroup
     */
    public function testThrowsExceptionWhenAddingExistingUserToGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(400)], $clientHistory);

        $this->expectExceptionObject(new RuntimeException('Unable to add user to group', 400));
        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->addUserToGroup('user-id', 'group-id');
    }

    /**
     * @covers ::removeUserFromGroup
     */
    public function testCanRemoveUserFromGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(204)], $clientHistory);

        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->removeUserFromGroup('user-id', 'group-id');
        $this->assertCount(1, $clientHistory);
        $this->assertSame('groups/group-id/members/user-id/$ref', (string) $clientHistory[0]['request']->getUri());
    }

    /**
     * @covers ::removeUserFromGroup
     */
    public function testThrowsExceptionWhenRemovingNonExistingMemberFromGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([new Response(400)], $clientHistory);

        $this->expectExceptionObject(new RuntimeException('Unable to remove user from group', 400));
        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->removeUserFromGroup('user-id', 'group-id');
    }

    /**
     * @covers ::emptyGroup
     */
    public function testCanEmptyGroup() : void {
        $clientHistory = [];
        $httpClient = $this->getMockClient([
            new Response(200, [], (string) json_encode([
                'value' => [
                    ['id' => 'id-1'],
                    ['id' => 'id-2'],
                    ['id' => 'id-3'],
                ],
            ])),
            new Response(204),
            new Response(204),
            new Response(204),
        ], $clientHistory);

        (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient(), $httpClient))->emptyGroup('group-id');

        $this->assertCount(4, $clientHistory, 'Expected 4 transactions');
        $this->assertSame('groups/group-id/members/id-1/$ref', (string) $clientHistory[1]['request']->getUri());
        $this->assertSame('groups/group-id/members/id-2/$ref', (string) $clientHistory[2]['request']->getUri());
        $this->assertSame('groups/group-id/members/id-3/$ref', (string) $clientHistory[3]['request']->getUri());
    }

    /**
     * @covers ::getUserFields
     * @covers ::setUserFields
     */
    public function testCanSetAndGetUserFields() : void {
        $client = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient()));
        $this->assertSame(['id', 'displayName', 'mail', 'accountEnabled'], $client->getUserFields());
        $client->setUserFields(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $client->getUserFields());
    }

    /**
     * @covers ::getGroupFields
     * @covers ::setGroupFields
     */
    public function testCanSetAndGetGroupFields() : void {
        $client = (new ApiClient('id', 'secret', 'nav.no', $this->getMockedAuthClient()));
        $this->assertSame(['id', 'displayName', 'description', 'mailNickname'], $client->getGroupFields());
        $client->setGroupFields(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $client->getGroupFields());
    }
}