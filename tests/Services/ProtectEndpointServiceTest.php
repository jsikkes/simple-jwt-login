<?php

namespace SimpleJwtLoginTests\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;

class ProtectEndpointServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressData
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->wordPressData = $this->getMockBuilder(WordPressData::class)
            ->getMock();
    }

    /**
     * @param bool $expectedResult
     * @param string $currentUrl
     * @param string $documentRoot
     * @param array $request
     * @param array $settings
     *
     * @dataProvider accessProvider
     */
    public function testHasAccess($expectedResult, $currentUrl, $documentRoot, $request, $settings)
    {
        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                ProtectEndpointSettings::PROPERTY_GROUP => $settings
            ]));
        $routeService = $this->getMockBuilder(RouteService::class)
            ->getMock();
        $routeService->method('getUserIdFromJWT')
            ->willReturn(0);

        $service = (new ProtectEndpointService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess($currentUrl,$documentRoot, $request);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array[]
     */
    public function accessProvider()
    {
        return [
            'test-not-enabled' => [
                'expectedResult' => true,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => 'test/'
                ],
                'settings' => [
                    'enabled' => false,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                    ],
                    'protect' => [
                        '/wp-json/v2/posts'
                    ],
                ]
            ],
            'test-enabled-all-endpoints' => [
                'expectedResult' => true,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                        '/wp-json/v2/posts'
                    ]
                ]
            ],
            'test-enabled-all-endpoints-with-no-whitelist' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                    ]
                ]
            ],
            'test-enabled-specific-endpoints' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp-json/wp/v2/posts'
                    ]
                ]
            ],
            'test-enabled-specific-endpoints-2' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/posts'
                    ]
                ]
            ],
        ];
    }
}
